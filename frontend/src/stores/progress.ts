import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

import { ApiError } from '@/api/http'
import { progressApi } from '@/api/progress'
import { useAuthStore } from '@/stores/auth'
import type { CourseModule, CourseProgress, LessonSummary, StepCompletion } from '@/types/api'

/** Шаги в браузере: «модуль/урок» → шаг → код (для проверки знаний — null) */
type LocalSteps = Record<string, Record<string, string | null>>

/** Прогресс модуля: сколько уроков пройдено и с какого продолжать */
export interface ModuleProgress {
  done: number
  total: number
  /** Пройдены все уроки модуля — вместе с контрольной: только тогда модуль пройден */
  complete: boolean
  /** Первый непройденный урок модуля */
  next: LessonSummary | null
}

/** Те же ограничения, что у CompleteStepsRequest бэкенда: иначе сервер отвергнет всю пачку */
const SLUG = /^[a-z0-9][a-z0-9-]{0,63}$/
const MAX_CODE = 20_000
const BATCH = 500

/**
 * Где лежат шаги: у гостя — его прогресс; у вошедшего — очередь шагов, которые не удалось
 * сохранить на сервере (нет сети, лимит запросов). Очередь своя у каждого аккаунта.
 */
const storageKey = (course: string, account: number | null) =>
  account === null ? `progress:${course}` : `progress:${course}:user:${account}`

const lessonPath = (module: string, lesson: string) => `${module}/${lesson}`

const isRecord = (value: unknown): value is Record<string, unknown> =>
  value !== null && typeof value === 'object' && !Array.isArray(value)

/** Из браузера — только то, что похоже на шаги: запись испортили — лишнее отбрасывается */
function readSteps(key: string): LocalSteps {
  let value: unknown
  try {
    value = JSON.parse(localStorage.getItem(key) ?? '{}')
  } catch {
    return {}
  }
  if (!isRecord(value)) {
    return {}
  }
  const steps: LocalSteps = {}
  for (const [path, lesson] of Object.entries(value)) {
    const [module = '', name = '', ...rest] = path.split('/')
    if (!SLUG.test(module) || !SLUG.test(name) || rest.length > 0 || !isRecord(lesson)) {
      continue
    }
    const valid = Object.entries(lesson).filter(
      (entry): entry is [string, string | null] =>
        SLUG.test(entry[0]) && (entry[1] === null || typeof entry[1] === 'string'),
    )
    if (valid.length > 0) {
      steps[path] = Object.fromEntries(valid)
    }
  }
  return steps
}

function writeSteps(key: string, steps: LocalSteps) {
  try {
    localStorage.setItem(key, JSON.stringify(steps))
  } catch {
    // без сохранения: прогресс до перезагрузки
  }
}

function removeSteps(key: string) {
  try {
    localStorage.removeItem(key)
  } catch {
    // нечего удалять
  }
}

function addStep(key: string, step: StepCompletion) {
  const steps = readSteps(key)
  const path = lessonPath(step.module, step.lesson)
  steps[path] = { ...steps[path], [step.step]: step.code ?? null }
  writeSteps(key, steps)
}

/** Шаги для сервера. Слишком длинный код не отправляется — шаг всё равно засчитывается */
function toCompletions(steps: LocalSteps): StepCompletion[] {
  return Object.entries(steps).flatMap(([path, lesson]) => {
    const [module = '', name = ''] = path.split('/')
    return Object.entries(lesson).map(([step, code]) => ({
      module,
      lesson: name,
      step,
      code: code !== null && code.length <= MAX_CODE ? code : null,
    }))
  })
}

/** Код решений урока из шагов в браузере */
function codesOf(steps: LocalSteps, path: string): Record<string, string> {
  return Object.fromEntries(
    Object.entries(steps[path] ?? {}).filter(
      (entry): entry is [string, string] => entry[1] !== null,
    ),
  )
}

/**
 * Прогресс ученика. Гость учится без аккаунта: прогресс — в браузере. Вошёл или
 * зарегистрировался — прогресс гостя переносится в аккаунт, дальше всё на сервере.
 */
export const useProgressStore = defineStore('progress', () => {
  const auth = useAuthStore()

  /** Курс, для которого нужен прогресс, и пройденные шаги по урокам «модуль/урок» */
  const course = ref<string | null>(null)
  const completed = ref<Record<string, string[]>>({})

  /** «курс|кто» того, что сейчас в completed, и загрузка, которая идёт */
  let loaded: string | null = null
  let pending: { key: string; promise: Promise<void> } | null = null
  /** Растёт с каждой загрузкой: ответ на старую (вышел, пока шёл запрос) не применяется */
  let generation = 0

  const account = () => auth.user?.id ?? null

  /** На экране — прогресс с сервера и то, что ещё не дошло до него из браузера */
  function show(slug: string, user: number, progress: CourseProgress | null) {
    completed.value = Object.fromEntries(
      (progress?.lessons ?? []).map((lesson) => [
        lessonPath(lesson.module, lesson.lesson),
        lesson.completed,
      ]),
    )
    merge(readSteps(storageKey(slug, user)))
    merge(readSteps(storageKey(slug, null)))
  }

  /** Добавить к пройденным шаги из браузера (ещё не дошедшие до сервера) */
  function merge(steps: LocalSteps) {
    const next = { ...completed.value }
    for (const [path, lesson] of Object.entries(steps)) {
      next[path] = [...new Set([...(next[path] ?? []), ...Object.keys(lesson)])]
    }
    completed.value = next
  }

  /**
   * Загрузить прогресс курса. Повторный вызов для того же курса и того же ученика ждёт
   * идущую загрузку или ничего не делает — карта, урок и вход не шлют одни и те же запросы.
   */
  async function load(slug: string): Promise<void> {
    course.value = slug
    await auth.restore()
    const key = `${slug}|${account() ?? 'guest'}`
    if (loaded === key) {
      return
    }
    if (pending?.key !== key) {
      const promise = fetchProgress(slug, key, ++generation).finally(() => {
        if (pending?.promise === promise) {
          pending = null
        }
      })
      pending = { key, promise }
    }
    return pending.promise
  }

  async function fetchProgress(slug: string, key: string, run: number) {
    const user = account()
    const guestKey = storageKey(slug, null)
    if (user === null) {
      completed.value = {}
      merge(readSteps(guestKey))
      loaded = key
      return
    }

    const ownKey = storageKey(slug, user)
    // Свои неотправленные шаги — свежие; прогресс гостя в этом браузере только дополняет аккаунт
    const sent = (await send(slug, ownKey, false)) && (await send(slug, guestKey, true))
    let progress: CourseProgress | null = null
    try {
      progress = await progressApi.course(slug)
    } catch {
      // нет сети — видно только то, что есть в браузере; задачи решать можно и так
    }
    if (run !== generation) {
      return
    }
    show(slug, user, progress)
    // Что-то не дошло до сервера — следующая загрузка попробует снова
    loaded = sent && progress !== null ? key : null
  }

  /**
   * Шаги из браузера — на сервер, пачками; дошли — стираются. Сервер их отверг (422) —
   * тоже стираются: повтор не поможет, а иначе они уходили бы при каждой загрузке.
   */
  async function send(slug: string, key: string, fromGuest: boolean): Promise<boolean> {
    const steps = toCompletions(readSteps(key))
    try {
      for (let start = 0; start < steps.length; start += BATCH) {
        await progressApi.complete(slug, steps.slice(start, start + BATCH), fromGuest)
      }
    } catch (error) {
      if (!(error instanceof ApiError && error.status === 422)) {
        return false
      }
    }
    if (steps.length > 0) {
      removeSteps(key)
    }
    return true
  }

  function isCompleted(module: string, lesson: string, step: string): boolean {
    return completed.value[lessonPath(module, lesson)]?.includes(step) ?? false
  }

  function isLessonDone(module: string, lesson: string, steps: string[]): boolean {
    return steps.length > 0 && steps.every((step) => isCompleted(module, lesson, step))
  }

  /**
   * Модуль пройден, когда пройдены все его уроки. Контрольная — последний урок модуля
   * (content/README.md), поэтому без неё модуль не пройти (rules/course-content.md).
   */
  function moduleProgress(module: CourseModule): ModuleProgress {
    const left = module.lessons.filter(
      (lesson) => !isLessonDone(module.slug, lesson.slug, lesson.steps),
    )
    const total = module.lessons.length
    return {
      done: total - left.length,
      total,
      complete: total > 0 && left.length === 0,
      next: left[0] ?? null,
    }
  }

  async function complete(slug: string, step: StepCompletion) {
    merge({ [lessonPath(step.module, step.lesson)]: { [step.step]: null } })

    const user = account()
    if (user === null) {
      addStep(storageKey(slug, null), step)
      return
    }
    const run = generation
    try {
      const progress = await progressApi.complete(slug, [step])
      if (run === generation) {
        show(slug, user, progress)
      }
    } catch {
      // Не сохранилось — в очередь аккаунта: уйдёт при следующей загрузке прогресса
      addStep(storageKey(slug, user), step)
      loaded = null
    }
  }

  /** Последний зачтённый код задач урока: подставить в редактор на другом устройстве */
  async function solutions(
    slug: string,
    module: string,
    lesson: string,
  ): Promise<Record<string, string>> {
    await auth.restore()
    const path = lessonPath(module, lesson)
    const guest = codesOf(readSteps(storageKey(slug, null)), path)
    const user = account()
    if (user === null) {
      return guest
    }
    let saved: Record<string, string> = {}
    try {
      saved = (await progressApi.lesson(slug, module, lesson)).solutions
    } catch {
      // нет сети — только то, что есть в браузере
    }
    // Как на сервере: гостевое лишь дополняет аккаунт, неотправленное своё — свежее всего
    return { ...guest, ...saved, ...codesOf(readSteps(storageKey(slug, user)), path) }
  }

  // Вошёл или вышел — прогресс того же курса заново: из аккаунта (с переносом гостевого) или из браузера
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
    completed,
    load,
    isCompleted,
    isLessonDone,
    moduleProgress,
    complete,
    solutions,
  }
})
