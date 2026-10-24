import { defineStore } from 'pinia'
import { ref, shallowRef, watch } from 'vue'

import { coursesApi } from '@/api/courses'
import { ApiError } from '@/api/http'
import { vocabularyApi } from '@/api/vocabulary'
import { locale } from '@/i18n/locale'
import { useAuthStore } from '@/stores/auth'
import type { CourseWord, LearnedWord, NewWord, WordReview } from '@/types/api'
import { fresh, isDue, LAST_BOX, schedule } from '@/utils/repetition'

/** Расписание слова в словаре: slug → коробка и когда повторить */
type Entries = Record<string, Omit<LearnedWord, 'word'>>

/** Неотправленное вошедшего: новые слова и ответы повторения — уходят по порядку */
interface Queue {
  add: string[]
  reviews: WordReview[]
}

/** Те же ограничения, что у запросов бэкенда: иначе сервер отвергнет всю пачку */
const SLUG = /^[a-z0-9][a-z0-9-]{0,63}$/
const BATCH = 500
/** Ответы повторения копятся и уходят пачкой: не запрос на каждое нажатие */
export const SYNC_DELAY = 1500

/** У гостя — его словарь; у вошедшего — очередь того, что ещё не дошло до сервера */
const storageKey = (course: string, account: number | null) =>
  account === null ? `vocabulary:${course}` : `vocabulary:${course}:user:${account}`

const isRecord = (value: unknown): value is Record<string, unknown> =>
  value !== null && typeof value === 'object' && !Array.isArray(value)

function readJson(key: string): unknown {
  try {
    return JSON.parse(localStorage.getItem(key) ?? 'null')
  } catch {
    return null
  }
}

function writeJson(key: string, value: unknown) {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch {
    // без сохранения: словарь до перезагрузки
  }
}

function remove(key: string) {
  try {
    localStorage.removeItem(key)
  } catch {
    // нечего удалять
  }
}

const validDate = (value: unknown): value is string =>
  typeof value === 'string' && !Number.isNaN(Date.parse(value))

/** Словарь гостя из браузера: испорченные записи отбрасываются */
function readEntries(key: string): Entries {
  const value = readJson(key)
  if (!isRecord(value)) {
    return {}
  }
  const entries: Entries = {}
  for (const [word, item] of Object.entries(value)) {
    if (
      SLUG.test(word) &&
      isRecord(item) &&
      Number.isInteger(item.box) &&
      Number(item.box) >= 0 &&
      Number(item.box) <= LAST_BOX &&
      validDate(item.due_at) &&
      (item.reviewed_at === null || validDate(item.reviewed_at))
    ) {
      entries[word] = { box: Number(item.box), due_at: item.due_at, reviewed_at: item.reviewed_at }
    }
  }
  return entries
}

function readQueue(key: string): Queue {
  const value = readJson(key)
  const queue: Queue = { add: [], reviews: [] }
  if (!isRecord(value)) {
    return queue
  }
  if (Array.isArray(value.add)) {
    queue.add = value.add.filter(
      (word): word is string => typeof word === 'string' && SLUG.test(word),
    )
  }
  if (Array.isArray(value.reviews)) {
    queue.reviews = value.reviews.filter(
      (item): item is WordReview =>
        isRecord(item) &&
        typeof item.word === 'string' &&
        SLUG.test(item.word) &&
        typeof item.remembered === 'boolean',
    )
  }
  return queue
}

function writeQueue(key: string, queue: Queue) {
  if (queue.add.length === 0 && queue.reviews.length === 0) {
    remove(key)
  } else {
    writeJson(key, queue)
  }
}

/** Список без отправленного: каждый отправленный элемент убирает одно своё первое совпадение */
function without<T>(list: T[], sent: T[], same: (a: T, b: T) => boolean): T[] {
  const rest = [...list]
  for (const item of sent) {
    const at = rest.findIndex((other) => same(other, item))
    if (at !== -1) {
      rest.splice(at, 1)
    }
  }
  return rest
}

/** Ответ повторения — к расписанию: засчитывается, только если слово пора повторять (как на сервере) */
function applyReview(entries: Entries, review: WordReview, now: Date): Entries {
  const entry = entries[review.word]
  if (!entry || !isDue(entry.due_at, now)) {
    return entries
  }
  const next = schedule(entry.box, review.remembered, now)
  return { ...entries, [review.word]: { ...next, reviewed_at: now.toISOString() } }
}

function addWords(entries: Entries, words: string[], now: Date): Entries {
  const missing = words.filter((word) => !(word in entries))
  if (missing.length === 0) {
    return entries
  }
  const next = { ...entries }
  for (const word of missing) {
    next[word] = { ...fresh(now), reviewed_at: null }
  }
  return next
}

/** Все страницы слов курса */
async function fetchAllWords(course: string): Promise<CourseWord[]> {
  const words: CourseWord[] = []
  let cursor: string | null = null
  do {
    const page = await coursesApi.words(course, cursor)
    words.push(...page.items)
    cursor = page.next_cursor
  } while (cursor !== null)
  return words
}

/**
 * Словарь ученика: выученные слова курса и когда их повторять. Гость учится без аккаунта: словарь —
 * в браузере. Вошёл — словарь гостя переносится в аккаунт, дальше всё на сервере; что не дошло (нет
 * сети, лимит) — ждёт в очереди браузера и уходит при следующей загрузке. Карточки слов — отдельно,
 * по курсу и языку (`cards`), они общие для всех.
 */
export const useVocabularyStore = defineStore('vocabulary', () => {
  const auth = useAuthStore()

  /** Курс, для которого нужен словарь, и его слова */
  const course = ref<string | null>(null)
  const entries = ref<Entries>({})

  /** «курс|кто» того, что сейчас в entries, и загрузка, которая идёт */
  let loaded: string | null = null
  let pending: { key: string; promise: Promise<void> } | null = null
  let generation = 0
  let timer: ReturnType<typeof setTimeout> | null = null
  /** Отправка очереди идёт: вторая ждёт её конца, иначе одно и то же ушло бы дважды */
  let sending: Promise<boolean> | null = null

  const account = () => auth.user?.id ?? null

  /** На экране — словарь с сервера и то, что из браузера ещё не дошло до него */
  function show(slug: string, user: number, words: LearnedWord[] | null) {
    let next: Entries = Object.fromEntries((words ?? []).map(({ word, ...entry }) => [word, entry]))
    const queue = readQueue(storageKey(slug, user))
    const now = new Date()
    next = addWords(next, queue.add, now)
    for (const review of queue.reviews) {
      next = applyReview(next, review, now)
    }
    entries.value = next
  }

  /** Загрузить словарь курса; повторный вызов для того же курса и ученика запросов не шлёт */
  async function load(slug: string): Promise<void> {
    course.value = slug
    await auth.restore()
    const key = `${slug}|${account() ?? 'guest'}`
    if (loaded === key) {
      return
    }
    if (pending?.key !== key) {
      const promise = fetchVocabulary(slug, key, ++generation).finally(() => {
        if (pending?.promise === promise) {
          pending = null
        }
      })
      pending = { key, promise }
    }
    return pending.promise
  }

  async function fetchVocabulary(slug: string, key: string, run: number) {
    const user = account()
    const guestKey = storageKey(slug, null)
    if (user === null) {
      entries.value = readEntries(guestKey)
      loaded = key
      return
    }

    // Своё неотправленное — свежее; словарь гостя в этом браузере только дополняет аккаунт
    const sent = (await flush(slug, user)) && (await sendGuest(slug, guestKey))
    let words: LearnedWord[] | null = null
    try {
      words = (await vocabularyApi.show(slug)).words
    } catch {
      // нет сети — видно только то, что ждёт в браузере
    }
    if (run !== generation) {
      return
    }
    show(slug, user, words)
    loaded = sent && words !== null ? key : null
  }

  /** Словарь гостя — в аккаунт пачками со своим расписанием; дошёл или отвергнут (422) — стирается */
  async function sendGuest(slug: string, key: string): Promise<boolean> {
    const words: NewWord[] = Object.entries(readEntries(key)).map(([word, entry]) => ({
      word,
      box: entry.box,
      due_at: entry.due_at,
    }))
    try {
      for (let start = 0; start < words.length; start += BATCH) {
        await vocabularyApi.add(slug, words.slice(start, start + BATCH), true)
      }
    } catch (error) {
      if (!(error instanceof ApiError && error.status === 422)) {
        return false
      }
    }
    if (words.length > 0) {
      remove(key)
    }
    return true
  }

  /**
   * Очередь вошедшего — на сервер: сначала новые слова, потом ответы (ответ на слово, которого нет на
   * сервере, не засчитается). Отправленное и отвергнутое (422 — повтор не поможет) убирается из очереди
   * по содержимому: что дописали за время запроса (и другая вкладка) — остаётся. Нет сети — дальше не
   * идём, остальное ждёт следующей загрузки.
   */
  function flush(slug: string, user: number): Promise<boolean> {
    const run = async (): Promise<boolean> => {
      if (sending) {
        await sending
      }
      const key = storageKey(slug, user)
      const queue = readQueue(key)
      const done: Queue = { add: [], reviews: [] }
      let result: LearnedWord[] | null = null

      async function stage<T>(
        items: T[],
        send: (batch: T[]) => Promise<{ words: LearnedWord[] }>,
        sent: T[],
      ): Promise<boolean> {
        for (let start = 0; start < items.length; start += BATCH) {
          const batch = items.slice(start, start + BATCH)
          try {
            result = (await send(batch)).words
          } catch (error) {
            if (!(error instanceof ApiError && error.status === 422)) {
              return false
            }
          }
          sent.push(...batch)
        }
        return true
      }

      const ok =
        (await stage(
          queue.add,
          (batch) =>
            vocabularyApi.add(
              slug,
              batch.map((word) => ({ word })),
            ),
          done.add,
        )) &&
        (await stage(queue.reviews, (batch) => vocabularyApi.review(slug, batch), done.reviews))

      const rest = readQueue(key)
      writeQueue(key, {
        add: without(rest.add, done.add, (a, b) => a === b),
        reviews: without(
          rest.reviews,
          done.reviews,
          (a, b) => a.word === b.word && a.remembered === b.remembered,
        ),
      })
      if (result !== null && course.value === slug && account() === user) {
        show(slug, user, result)
      }
      return ok
    }
    const promise = run().finally(() => {
      if (sending === promise) {
        sending = null
      }
    })
    sending = promise
    return promise
  }

  /** Отправить очередь сейчас (уход со страницы повторения) или чуть позже (ответы подряд) */
  function sync(slug: string, delay = 0): Promise<void> | void {
    const user = account()
    if (user === null) {
      return
    }
    if (timer !== null) {
      clearTimeout(timer)
      timer = null
    }
    if (delay > 0) {
      timer = setTimeout(() => {
        timer = null
        void flush(slug, user).then((ok) => ok || (loaded = null))
      }, delay)
      return
    }
    return flush(slug, user).then((ok) => {
      if (!ok) {
        loaded = null
      }
    })
  }

  /** Слова урока — в словарь (уже выученные не меняются) */
  async function add(slug: string, words: string[]): Promise<void> {
    await auth.restore()
    const valid = words.filter((word) => SLUG.test(word))
    const user = account()
    const now = new Date()
    if (user === null) {
      const key = storageKey(slug, null)
      const stored = readEntries(key)
      const next = addWords(stored, valid, now)
      if (next !== stored) {
        writeJson(key, next)
      }
      if (course.value === slug) {
        entries.value = addWords(entries.value, valid, now)
      }
      return
    }
    const missing = valid.filter((word) => !(course.value === slug && word in entries.value))
    if (missing.length === 0) {
      return
    }
    if (course.value === slug) {
      entries.value = addWords(entries.value, missing, now)
    }
    const key = storageKey(slug, user)
    const queue = readQueue(key)
    writeQueue(key, { ...queue, add: [...queue.add, ...missing] })
    await sync(slug)
  }

  /** Ответ повторения: расписание меняется сразу, на сервер уходит пачкой */
  function review(slug: string, word: string, remembered: boolean) {
    const now = new Date()
    const item = { word, remembered }
    entries.value = applyReview(entries.value, item, now)
    const user = account()
    if (user === null) {
      writeJson(storageKey(slug, null), applyReview(readEntries(storageKey(slug, null)), item, now))
      return
    }
    const key = storageKey(slug, user)
    const queue = readQueue(key)
    writeQueue(key, { ...queue, reviews: [...queue.reviews, item] })
    void sync(slug, SYNC_DELAY)
  }

  /**
   * Слова словаря, которые есть в курсе: карточку могли убрать или переименовать, а запись в браузере
   * осталась. Пока карточки не загружены — все слова
   */
  function learnedWords(): string[] {
    const slug = course.value
    const catalog = slug === null ? null : cardsAnyLocale(slug)
    const known = catalog === null ? null : new Set(catalog.map((card) => card.id))
    return Object.keys(entries.value).filter((word) => known === null || known.has(word))
  }

  /** Слова, которые пора повторить (по часам ученика) */
  function dueWords(now = new Date()): string[] {
    const known = new Set(learnedWords())
    return Object.entries(entries.value)
      .filter(([word, entry]) => known.has(word) && isDue(entry.due_at, now))
      .sort(([, a], [, b]) => a.due_at.localeCompare(b.due_at))
      .map(([word]) => word)
  }

  /** «курс|язык» → карточки слов курса */
  const catalogs = shallowRef<Record<string, CourseWord[]>>({})
  const catalogPending = new Map<string, Promise<CourseWord[]>>()

  /** Карточки слов курса на текущем языке: из памяти, а нет — с сервера. Ошибка не запоминается */
  function loadCards(slug: string): Promise<CourseWord[]> {
    const key = `${slug}|${locale.value}`
    const cached = catalogs.value[key]
    if (cached) {
      return Promise.resolve(cached)
    }
    let request = catalogPending.get(key)
    if (!request) {
      request = fetchAllWords(slug)
        .then((words) => {
          catalogs.value = { ...catalogs.value, [key]: words }
          return words
        })
        .finally(() => catalogPending.delete(key))
      catalogPending.set(key, request)
    }
    return request
  }

  /** Карточки курса на любом языке — для id слов, тексты не нужны */
  function cardsAnyLocale(slug: string): CourseWord[] | null {
    const key = Object.keys(catalogs.value).find((item) => item.startsWith(`${slug}|`))
    return key === undefined ? null : (catalogs.value[key] ?? null)
  }

  /** Загруженные карточки слов курса на текущем языке (реактивно) или null */
  function cards(slug: string): CourseWord[] | null {
    return catalogs.value[`${slug}|${locale.value}`] ?? null
  }

  // Вошёл или вышел — словарь того же курса заново: из аккаунта (с переносом гостевого) или из браузера
  watch(
    () => auth.status,
    (status) => {
      if (course.value !== null && status !== 'unknown') {
        void load(course.value)
      }
    },
  )

  return {
    course,
    entries,
    load,
    add,
    review,
    sync,
    learnedWords,
    dueWords,
    loadCards,
    cards,
  }
})
