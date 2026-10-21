import { flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import type { StepCompletion } from '@/types/api'

import { twoModules } from './lessonFixtures'

const fetchMock = vi.fn<typeof fetch>()
const reply = (data: unknown) =>
  new Response(JSON.stringify({ status: 'success', message: '', data, errors: {} }), {
    status: 200,
  })
const user = { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'ru' }
const courseProgress = (completed: string[]) => ({
  lessons: [
    { module: 'm', lesson: 'l', steps: ['task', 'quiz'], completed, done: completed.length === 2 },
  ],
})

const failure = (status: number) =>
  new Response(JSON.stringify({ status: 'error', message: 'нет', data: null, errors: {} }), {
    status,
  })

/** Ответы по адресу запроса, а не по порядку: так видно, сколько запросов ушло на самом деле */
function serve(routes: { me?: () => Response; steps?: () => Response; course?: () => Response }) {
  fetchMock.mockImplementation(async (input, init) => {
    const url = String(input)
    if (url.endsWith('/auth/me')) {
      return (routes.me ?? (() => reply(user)))()
    }
    if (url.endsWith('/steps') && init?.method === 'POST') {
      return (routes.steps ?? (() => reply(courseProgress(['task']))))()
    }
    return (routes.course ?? (() => reply(courseProgress(['task']))))()
  })
}

const requests = (pattern: RegExp) =>
  fetchMock.mock.calls.filter(([url, init]) =>
    pattern.test(`${init?.method ?? 'GET'} ${String(url)}`),
  )
const bodies = (pattern: RegExp) =>
  requests(pattern).map(([, init]) => JSON.parse(String(init?.body)) as unknown)

/** Тело последнего запроса и его адрес */
function lastRequest() {
  const [url, init] = fetchMock.mock.calls[fetchMock.mock.calls.length - 1]!
  return { url: String(url), body: init?.body ? (JSON.parse(String(init.body)) as unknown) : null }
}

describe('useProgressStore', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
    tokenStorage.clear()
    setActivePinia(createPinia())
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('гость: прогресс и код решения — в браузере, без запросов', async () => {
    const progress = useProgressStore()
    await progress.load('csharp')

    await progress.complete('csharp', { module: 'm', lesson: 'l', step: 'task', code: 'код' })
    await progress.complete('csharp', { module: 'm', lesson: 'l', step: 'quiz' })

    expect(progress.isLessonDone('m', 'l', ['task', 'quiz'])).toBe(true)
    expect(await progress.solutions('csharp', 'm', 'l')).toEqual({ task: 'код' })
    expect(fetchMock).not.toHaveBeenCalled()

    // После перезагрузки — тот же прогресс
    setActivePinia(createPinia())
    const reloaded = useProgressStore()
    await reloaded.load('csharp')
    expect(reloaded.isCompleted('m', 'l', 'task')).toBe(true)
  })

  it('вошедший: прогресс с сервера, решённый шаг — на сервер', async () => {
    tokenStorage.set('1|t')
    fetchMock
      .mockResolvedValueOnce(reply(user))
      .mockResolvedValueOnce(reply(courseProgress([])))
      .mockResolvedValueOnce(reply(courseProgress(['task'])))
    const progress = useProgressStore()

    await progress.load('csharp')
    await progress.complete('csharp', { module: 'm', lesson: 'l', step: 'task', code: 'код' })

    expect(lastRequest()).toEqual({
      url: expect.stringMatching(/\/api\/progress\/courses\/csharp\/steps$/),
      body: { steps: [{ module: 'm', lesson: 'l', step: 'task', code: 'код' }] },
    })
    expect(progress.isCompleted('m', 'l', 'task')).toBe(true)
    expect(localStorage.getItem('progress:csharp')).toBeNull()
  })

  it('гость вошёл — его прогресс переносится в аккаунт и стирается из браузера', async () => {
    const progress = useProgressStore()
    await progress.load('csharp')
    await progress.complete('csharp', { module: 'm', lesson: 'l', step: 'task', code: 'код гостя' })

    fetchMock
      .mockResolvedValueOnce(reply({ user, token: '1|t' }))
      .mockResolvedValueOnce(reply(courseProgress(['task'])))
      .mockResolvedValue(reply(courseProgress(['task'])))
    await useAuthStore().login({ email: 'anya@example.com', password: 'password1' })
    await flushPromises()

    const transfer = fetchMock.mock.calls.find(([url]) => String(url).endsWith('/steps'))
    expect(JSON.parse(String(transfer?.[1]?.body))).toEqual({
      steps: [{ module: 'm', lesson: 'l', step: 'task', code: 'код гостя' }],
      guest: true,
    })
    expect(localStorage.getItem('progress:csharp')).toBeNull()
    expect(progress.isCompleted('m', 'l', 'task')).toBe(true)
  })

  it('перенос не удался — прогресс гостя остаётся в браузере', async () => {
    localStorage.setItem('progress:csharp', JSON.stringify({ 'm/l': { quiz: null } }))
    tokenStorage.set('1|t')
    fetchMock.mockResolvedValueOnce(reply(user)).mockRejectedValue(new TypeError('Failed to fetch'))

    await useProgressStore().load('csharp')

    expect(localStorage.getItem('progress:csharp')).not.toBeNull()
  })

  it('карта, урок и вход вместе — один перенос и одна загрузка прогресса', async () => {
    localStorage.setItem('progress:csharp', JSON.stringify({ 'm/l': { quiz: null } }))
    tokenStorage.set('1|t')
    serve({})
    const progress = useProgressStore()

    await Promise.all([progress.load('csharp'), progress.load('csharp')])
    await flushPromises()
    await progress.load('csharp')

    expect(requests(/^GET .*\/auth\/me$/)).toHaveLength(1)
    expect(requests(/^POST .*\/steps$/)).toHaveLength(1)
    expect(requests(/^GET .*\/progress\/courses\/csharp$/)).toHaveLength(1)
  })

  it('вышел — снова прогресс гостя, а не аккаунта', async () => {
    localStorage.setItem('progress:csharp', JSON.stringify({}))
    tokenStorage.set('1|t')
    serve({ steps: () => reply(null) })
    const progress = useProgressStore()
    await progress.load('csharp')
    expect(progress.isCompleted('m', 'l', 'task')).toBe(true)

    await useAuthStore().logout()
    await flushPromises()

    expect(progress.isCompleted('m', 'l', 'task')).toBe(false)
  })

  it('ответ, пришедший после выхода, не показывается гостю', async () => {
    tokenStorage.set('1|t')
    let answer: (response: Response) => void = () => {}
    serve({ course: () => reply(null) })
    fetchMock.mockImplementationOnce(async () => reply(user))
    fetchMock.mockImplementationOnce(() => new Promise((resolve) => (answer = resolve)))
    const progress = useProgressStore()
    const loading = progress.load('csharp')
    await flushPromises()

    await useAuthStore().logout()
    await flushPromises()
    answer(reply(courseProgress(['task', 'quiz'])))
    await loading

    expect(progress.isCompleted('m', 'l', 'task')).toBe(false)
  })

  it('испорченная запись в браузере не ломает прогресс: берётся только похожее на шаги', async () => {
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ 'm/l': null, x: 'строка', '../a/b': {}, 'm/ok': { quiz: null, bad: 5 } }),
    )
    const progress = useProgressStore()

    await expect(progress.load('csharp')).resolves.toBeUndefined()

    expect(progress.completed).toEqual({ 'm/ok': ['quiz'] })
  })

  it('перенос: пачки по 500, длинный код не отправляется, а отвергнутое сервером стирается', async () => {
    const steps = Object.fromEntries(Array.from({ length: 501 }, (_, i) => [`s${i}`, null]))
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ 'm/l': { ...steps, task: 'a'.repeat(20_001) } }),
    )
    tokenStorage.set('1|t')
    serve({ steps: () => failure(422) })

    await useProgressStore().load('csharp')

    const sent = bodies(/^POST .*\/steps$/) as { steps: StepCompletion[]; guest: boolean }[]
    expect(sent.map((body) => body.steps.length)).toEqual([500])
    expect(sent[0]?.guest).toBe(true)
    expect(localStorage.getItem('progress:csharp')).toBeNull()

    // Без отказа сервера — обе пачки, у длинного кода — только отметка о шаге
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ 'm/l': { ...steps, task: 'a'.repeat(20_001) } }),
    )
    fetchMock.mockReset()
    serve({})
    setActivePinia(createPinia())
    await useProgressStore().load('csharp')
    const batches = bodies(/^POST .*\/steps$/) as { steps: StepCompletion[] }[]
    expect(batches.map((body) => body.steps.length)).toEqual([500, 2])
    expect(batches[1]?.steps.find((step) => step.step === 'task')?.code).toBeNull()
  })

  it('вошедший без сети: шаг не теряется — ждёт в браузере и уходит при следующей загрузке', async () => {
    tokenStorage.set('1|t')
    serve({ steps: () => failure(503), course: () => reply(courseProgress([])) })
    const progress = useProgressStore()
    await progress.load('csharp')

    await progress.complete('csharp', { module: 'm', lesson: 'l', step: 'task', code: 'новое' })

    expect(progress.isCompleted('m', 'l', 'task')).toBe(true)
    expect(localStorage.getItem('progress:csharp:user:1')).not.toBeNull()
    serve({ course: () => reply(courseProgress([])), steps: () => reply(null) })
    fetchMock.mockImplementationOnce(async () =>
      reply({ completed: ['task'], solutions: { task: 'старое' } }),
    )
    expect(await progress.solutions('csharp', 'm', 'l')).toEqual({ task: 'новое' })

    fetchMock.mockClear()
    await progress.load('csharp')

    expect(bodies(/^POST .*\/steps$/)).toEqual([
      { steps: [{ module: 'm', lesson: 'l', step: 'task', code: 'новое' }] },
    ])
    expect(localStorage.getItem('progress:csharp:user:1')).toBeNull()
  })

  it('модуль пройден, только когда пройдены все его уроки вместе с контрольной', async () => {
    const progress = useProgressStore()
    await progress.load('csharp')
    const [first, second] = twoModules.modules

    expect(progress.moduleProgress(first!)).toEqual({
      done: 0,
      total: 2,
      complete: false,
      next: first!.lessons[0],
    })

    // Контрольная пройдена, первый урок — нет: модуль не пройден, продолжать — с первого урока
    await progress.complete('csharp', {
      module: first!.slug,
      lesson: '02-module-test',
      step: 'goblin',
      code: 'код',
    })
    await progress.complete('csharp', {
      module: first!.slug,
      lesson: '02-module-test',
      step: 'quiz',
    })
    expect(progress.moduleProgress(first!)).toMatchObject({ done: 1, complete: false })
    expect(progress.moduleProgress(first!).next?.slug).toBe('01-hello')

    await progress.complete('csharp', {
      module: first!.slug,
      lesson: '01-hello',
      step: 'hello-world',
      code: 'код',
    })
    await progress.complete('csharp', { module: first!.slug, lesson: '01-hello', step: 'quiz' })
    expect(progress.moduleProgress(first!)).toEqual({
      done: 2,
      total: 2,
      complete: true,
      next: null,
    })
    // Соседний модуль не задет
    expect(progress.moduleProgress(second!)).toMatchObject({ done: 0, complete: false })
  })

  it('модуль без уроков не считается пройденным', () => {
    const progress = useProgressStore()

    expect(progress.moduleProgress({ ...twoModules.modules[0]!, lessons: [] })).toEqual({
      done: 0,
      total: 0,
      complete: false,
      next: null,
    })
  })
})
