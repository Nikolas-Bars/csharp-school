import { flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { DEFAULT_LOCALE, setLocale } from '@/i18n/locale'
import { COURSE_MAP_TTL, useCourseStore } from '@/stores/course'

import { apiReply, course, twoModules } from './lessonFixtures'

const fetchMock = vi.fn<typeof fetch>()
const failure = () =>
  new Response(JSON.stringify({ status: 'error', message: 'Сбой', data: null, errors: {} }), {
    status: 500,
  })

describe('useCourseStore', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    setActivePinia(createPinia())
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
    vi.useRealTimers()
    setLocale(DEFAULT_LOCALE)
  })

  it('карта курса загружается один раз: и карта, и уроки берут её же', async () => {
    fetchMock.mockImplementation(async () => apiReply(course))
    const courses = useCourseStore()

    expect(courses.map('csharp')).toBeNull()
    const [first, second] = await Promise.all([courses.load('csharp'), courses.load('csharp')])
    const third = await courses.load('csharp')

    expect(first).toEqual(course)
    expect(second).toEqual(course)
    expect(third).toBe(courses.map('csharp'))
    expect(fetchMock).toHaveBeenCalledTimes(1)
    expect(String(fetchMock.mock.calls[0]?.[0])).toMatch(/\/api\/courses\/csharp$/)
  })

  it('другой курс или другой язык — свой запрос и своя карта', async () => {
    fetchMock.mockImplementation(async () => apiReply(course))
    const courses = useCourseStore()

    await courses.load('csharp')
    await courses.load('unity')
    setLocale('en')
    expect(courses.map('csharp')).toBeNull()
    await courses.load('csharp')

    expect(fetchMock).toHaveBeenCalledTimes(3)
    expect(new Headers(fetchMock.mock.calls[2]?.[1]?.headers).get('Accept-Language')).toBe('en')
  })

  it('не загрузилась — ошибка не запоминается, следующий вызов пробует снова', async () => {
    fetchMock.mockResolvedValueOnce(failure()).mockImplementation(async () => apiReply(course))
    const courses = useCourseStore()

    await expect(courses.load('csharp')).rejects.toThrow('Сбой')
    await expect(courses.load('csharp')).resolves.toEqual(course)
    expect(fetchMock).toHaveBeenCalledTimes(2)
  })

  it('refresh — сразу карта из памяти, новая с сервера заменяет её в фоне', async () => {
    fetchMock.mockImplementationOnce(async () => apiReply(course))
    const courses = useCourseStore()
    await courses.load('csharp')

    // После course:import на сервере — другая карта
    fetchMock.mockImplementationOnce(async () => apiReply(twoModules))
    await expect(courses.load('csharp', { refresh: true })).resolves.toEqual(course)
    await flushPromises()

    expect(courses.map('csharp')).toEqual(twoModules)
    expect(fetchMock).toHaveBeenCalledTimes(2)
  })

  it('обновление в фоне не удалось — остаётся прежняя карта', async () => {
    fetchMock.mockImplementationOnce(async () => apiReply(course))
    const courses = useCourseStore()
    await courses.load('csharp')

    fetchMock.mockResolvedValueOnce(failure())
    await courses.load('csharp', { refresh: true })
    await flushPromises()

    expect(courses.map('csharp')).toEqual(course)
  })

  it('карта старше COURSE_MAP_TTL обновляется в фоне и без refresh', async () => {
    vi.useFakeTimers({ toFake: ['Date'] })
    fetchMock.mockImplementation(async () => apiReply(course))
    const courses = useCourseStore()
    await courses.load('csharp')

    await courses.load('csharp')
    expect(fetchMock).toHaveBeenCalledTimes(1)

    vi.setSystemTime(Date.now() + COURSE_MAP_TTL + 1)
    await courses.load('csharp')
    await flushPromises()
    expect(fetchMock).toHaveBeenCalledTimes(2)
  })
})
