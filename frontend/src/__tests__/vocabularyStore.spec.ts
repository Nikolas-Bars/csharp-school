import { flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import { useAuthStore } from '@/stores/auth'
import { SYNC_DELAY, useVocabularyStore } from '@/stores/vocabulary'
import type { LearnedWord } from '@/types/api'

import { apiReply, courseWords } from './lessonFixtures'

const fetchMock = vi.fn<typeof fetch>()
const user = { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'ru' }
const failure = (status: number) =>
  new Response(JSON.stringify({ status: 'error', message: 'нет', data: null, errors: {} }), {
    status,
  })
const learned = (word: string, box = 0): LearnedWord => ({
  word,
  box,
  due_at: new Date().toISOString(),
  reviewed_at: null,
})

/** Сервер словаря: помнит слова, отвечает словарём; down — сеть упала */
function serve({
  words = [] as LearnedWord[],
  down = false,
  reject = false,
  rejectWords = false,
} = {}) {
  const state = {
    words: [...words],
    down,
    reject,
    rejectWords,
    pause: null as Promise<void> | null,
  }
  fetchMock.mockImplementation(async (input, init) => {
    const url = String(input)
    if (url.endsWith('/auth/me')) {
      return apiReply(user)
    }
    if (url.endsWith('/auth/login')) {
      return apiReply({ user, token: 'token' })
    }
    if (!url.includes('/vocabulary/') && url.includes('/courses/english/words')) {
      return apiReply({ items: courseWords, next_cursor: null })
    }
    if (state.down) {
      throw new TypeError('сеть')
    }
    if (state.reject) {
      return failure(422)
    }
    if (state.pause) {
      await state.pause
    }
    if (url.endsWith('/words') && init?.method === 'POST' && state.rejectWords) {
      return failure(422)
    }
    if (url.endsWith('/words') && init?.method === 'POST') {
      const body = JSON.parse(String(init.body)) as { words: { word: string }[] }
      for (const { word } of body.words) {
        if (!state.words.some((item) => item.word === word)) {
          state.words.push(learned(word))
        }
      }
    }
    if (url.endsWith('/reviews') && init?.method === 'POST') {
      const body = JSON.parse(String(init.body)) as { reviews: { word: string }[] }
      state.words = state.words.map((item) =>
        body.reviews.some((review) => review.word === item.word) ? { ...item, box: 1 } : item,
      )
    }
    return apiReply({ words: state.words })
  })
  return state
}

const requests = (pattern: RegExp) =>
  fetchMock.mock.calls.filter(([url, init]) =>
    pattern.test(`${init?.method ?? 'GET'} ${String(url)}`),
  )
const bodies = (pattern: RegExp) =>
  requests(pattern).map(([, init]) => JSON.parse(String(init?.body)) as unknown)

async function login() {
  tokenStorage.set('token')
  await useAuthStore().restore()
}

describe('useVocabularyStore', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
    tokenStorage.clear()
    setActivePinia(createPinia())
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
    vi.useRealTimers()
  })

  it('гость: слова и повторение — в браузере, без запросов', async () => {
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    await vocabulary.add('english', ['wear', 'at-the-moment', 'Bad Slug'])
    expect(vocabulary.dueWords()).toEqual(['wear', 'at-the-moment'])

    vocabulary.review('english', 'wear', true)
    vocabulary.review('english', 'at-the-moment', false)
    // Повтор того же слова, пока его не пора повторять, ничего не сдвигает
    vocabulary.review('english', 'wear', true)

    expect(vocabulary.entries.wear?.box).toBe(1)
    expect(vocabulary.entries['at-the-moment']?.box).toBe(0)
    expect(vocabulary.dueWords()).toEqual([])
    expect(fetchMock).not.toHaveBeenCalled()

    // После перезагрузки — тот же словарь
    setActivePinia(createPinia())
    const again = useVocabularyStore()
    await again.load('english')
    expect(again.entries.wear?.box).toBe(1)
  })

  it('выученное слово при повторном уроке не сбрасывается', async () => {
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')
    await vocabulary.add('english', ['wear'])
    vocabulary.review('english', 'wear', true)

    await vocabulary.add('english', ['wear'])

    expect(vocabulary.entries.wear?.box).toBe(1)
  })

  it('испорченная запись в браузере отбрасывается', async () => {
    localStorage.setItem(
      'vocabulary:english',
      JSON.stringify({
        wear: { box: 9, due_at: 'вчера', reviewed_at: null },
        ok: { box: 1, due_at: new Date().toISOString(), reviewed_at: null },
      }),
    )
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    expect(Object.keys(vocabulary.entries)).toEqual(['ok'])
  })

  it('вошедший: слова — на сервер, ответы повторения — пачкой', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    serve()
    await login()
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    await vocabulary.add('english', ['wear', 'at-the-moment'])
    expect(bodies(/POST .*vocabulary\/courses\/english\/words$/)).toEqual([
      { words: [{ word: 'wear' }, { word: 'at-the-moment' }] },
    ])

    vocabulary.review('english', 'wear', true)
    vocabulary.review('english', 'at-the-moment', true)
    expect(requests(/reviews/)).toHaveLength(0)

    await vi.advanceTimersByTimeAsync(SYNC_DELAY)
    await flushPromises()
    expect(bodies(/POST .*reviews$/)).toEqual([
      {
        reviews: [
          { word: 'wear', remembered: true },
          { word: 'at-the-moment', remembered: true },
        ],
      },
    ])
    expect(localStorage.getItem('vocabulary:english:user:1')).toBeNull()
  })

  it('нет сети — очередь ждёт в браузере и уходит при следующей загрузке', async () => {
    const server = serve({ down: true })
    await login()
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    await vocabulary.add('english', ['wear'])
    vocabulary.review('english', 'wear', false)
    await vocabulary.sync('english')
    // На экране слово уже есть
    expect(vocabulary.entries.wear?.box).toBe(0)
    expect(localStorage.getItem('vocabulary:english:user:1')).not.toBeNull()

    server.down = false
    setActivePinia(createPinia())
    await useAuthStore().restore()
    const again = useVocabularyStore()
    await again.load('english')

    expect(bodies(/POST .*\/words$/)).toContainEqual({ words: [{ word: 'wear' }] })
    expect(bodies(/POST .*reviews$/)).toContainEqual({
      reviews: [{ word: 'wear', remembered: false }],
    })
    expect(localStorage.getItem('vocabulary:english:user:1')).toBeNull()
    expect(again.entries.wear).toBeDefined()
  })

  it('отвергнутое сервером (422) из очереди убирается', async () => {
    serve({ reject: true })
    await login()
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    await vocabulary.add('english', ['wear'])

    expect(localStorage.getItem('vocabulary:english:user:1')).toBeNull()
  })

  it('вход: словарь гостя переносится в аккаунт со своим расписанием', async () => {
    serve({ words: [learned('at-the-moment', 2)] })
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')
    await vocabulary.add('english', ['wear'])
    vocabulary.review('english', 'wear', true)

    // Вошёл — словарь того же курса загружается заново, с переносом гостевого
    await useAuthStore().login({ email: 'anya@example.com', password: 'password1' })
    await flushPromises()
    await vocabulary.load('english')

    const [body] = bodies(/POST .*\/words$/) as {
      words: Record<string, unknown>[]
      guest: boolean
    }[]
    expect(body?.guest).toBe(true)
    expect(body?.words).toEqual([{ word: 'wear', box: 1, due_at: expect.any(String) as unknown }])
    expect(localStorage.getItem('vocabulary:english')).toBeNull()
    expect(Object.keys(vocabulary.entries).sort()).toEqual(['at-the-moment', 'wear'])
  })

  it('повторная загрузка того же курса запросов не шлёт', async () => {
    serve()
    await login()
    const vocabulary = useVocabularyStore()
    await Promise.all([vocabulary.load('english'), vocabulary.load('english')])
    await vocabulary.load('english')

    expect(requests(/GET .*vocabulary\/courses\/english$/)).toHaveLength(1)
  })

  it('карточки курса — все страницы, один раз на язык', async () => {
    serve()
    const vocabulary = useVocabularyStore()

    const cards = await vocabulary.loadCards('english')
    await vocabulary.loadCards('english')

    expect(cards.map((card) => card.id)).toEqual(['wear', 'at-the-moment'])
    expect(vocabulary.cards('english')).toHaveLength(2)
    expect(requests(/GET .*api\/courses\/english\/words/)).toHaveLength(1)
  })

  it('422 на новых словах не выбрасывает ответы повторения из очереди', async () => {
    serve({ words: [learned('wear')], rejectWords: true })
    await login()
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    vocabulary.review('english', 'wear', true)
    await vocabulary.add('english', ['at-the-moment'])

    expect(bodies(/POST .*reviews$/)).toEqual([{ reviews: [{ word: 'wear', remembered: true }] }])
    expect(localStorage.getItem('vocabulary:english:user:1')).toBeNull()
  })

  it('ответ, записанный во время отправки, остаётся в очереди', async () => {
    const server = serve({ words: [learned('wear'), learned('at-the-moment')] })
    await login()
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')

    let resume = () => {}
    server.pause = new Promise((resolve) => (resume = resolve))
    vocabulary.review('english', 'wear', true)
    const first = vocabulary.sync('english')
    await flushPromises()
    vocabulary.review('english', 'at-the-moment', false)
    server.pause = null
    resume()
    await first

    const queue = JSON.parse(localStorage.getItem('vocabulary:english:user:1') ?? '{}') as unknown
    expect(queue).toEqual({ add: [], reviews: [{ word: 'at-the-moment', remembered: false }] })
  })

  it('слова, которых нет в курсе, не считаются ни выученными, ни ждущими', async () => {
    serve()
    localStorage.setItem(
      'vocabulary:english',
      JSON.stringify({
        wear: { box: 0, due_at: new Date().toISOString(), reviewed_at: null },
        removed: { box: 0, due_at: new Date().toISOString(), reviewed_at: null },
      }),
    )
    const vocabulary = useVocabularyStore()
    await vocabulary.load('english')
    expect(vocabulary.dueWords()).toHaveLength(2)

    await vocabulary.loadCards('english')

    expect(vocabulary.learnedWords()).toEqual(['wear'])
    expect(vocabulary.dueWords()).toEqual(['wear'])
  })
})
