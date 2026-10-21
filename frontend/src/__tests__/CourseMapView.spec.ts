import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { DEFAULT_LOCALE, setLocale } from '@/i18n/locale'
import CourseMapView from '@/views/CourseMapView.vue'

import { apiReply, course, twoModules } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

// Смонтированные страницы следят за языком: после теста — размонтировать
enableAutoUnmount(afterEach)

describe('CourseMapView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
    setLocale(DEFAULT_LOCALE)
  })

  it('смена языка — карта загружается заново на новом языке', async () => {
    fetchMock.mockImplementation(async () => apiReply(course))
    mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    setLocale('en')
    await flushPromises()

    expect(fetchMock).toHaveBeenCalledTimes(2)
    expect(new Headers(fetchMock.mock.calls[1]?.[1]?.headers).get('Accept-Language')).toBe('en')
  })

  it('повторный заход — карта из памяти сразу, обновлённая с сервера заменяет её', async () => {
    const pinia = createPinia()
    fetchMock.mockImplementationOnce(async () => apiReply(course))
    mount(CourseMapView, { global: { plugins: [pinia, await testRouter('/courses/csharp')] } })
    await flushPromises()

    // После course:import на сервере — новая карта; ответ придёт позже
    let respond: (value: Response) => void = () => {}
    fetchMock.mockImplementationOnce(() => new Promise((resolve) => (respond = resolve)))
    const again = mount(CourseMapView, {
      global: { plugins: [pinia, await testRouter('/courses/csharp')] },
    })
    await flushPromises()
    expect(again.findAll('.map__module')).toHaveLength(1)

    respond(apiReply(twoModules))
    await flushPromises()
    expect(again.findAll('.map__module')).toHaveLength(2)
    expect(fetchMock).toHaveBeenCalledTimes(2)
  })

  it('модули и уроки по порядку, со ссылками и числом задач', async () => {
    fetchMock.mockResolvedValue(apiReply(course))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    expect(String(fetchMock.mock.calls[0]?.[0])).toMatch(/\/api\/courses\/csharp$/)
    expect(wrapper.find('h1').text()).toBe('Первые программы')
    expect(
      wrapper.findAll('.map__lesson').map((link) => [link.text(), link.attributes('href')]),
    ).toEqual([
      ['Сейчас1 задача1. Первая программа', '/courses/csharp/01-first-programs/01-hello'],
      ['Урок 2Без задач2. Ошибки — это нормально', '/courses/csharp/01-first-programs/02-errors'],
    ])
    expect(wrapper.find('.map__cta').text()).toBe('Начать: Первая программа')
  })

  it('ошибка загрузки — текст сервера и повтор', async () => {
    fetchMock.mockResolvedValue(
      new Response(
        JSON.stringify({ status: 'error', message: 'Такого курса нет.', data: null, errors: {} }),
        {
          status: 404,
        },
      ),
    )
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/nope')] },
    })
    await flushPromises()

    expect(wrapper.get('[role="alert"]').text()).toContain('Такого курса нет.')
  })

  it('прогресс гостя из браузера: пройденный урок, «Сейчас» и счётчик', async () => {
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null } }),
    )
    fetchMock.mockResolvedValue(apiReply(course))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    expect(wrapper.findAll('.map__badge').map((badge) => badge.text())).toEqual([
      'Пройден',
      'Сейчас',
    ])
    expect(wrapper.get('[aria-current="step"]').text()).toContain('Ошибки — это нормально')
    expect(wrapper.get('.map__count').text()).toBe('1 / 2')
    expect(wrapper.get('.map__cta').text()).toBe('Продолжить: Ошибки — это нормально')
    // Гость — прогресс только из браузера, запрос один: карта курса
    expect(fetchMock).toHaveBeenCalledTimes(1)
  })

  it('модуль пройден, когда пройдены все его уроки вместе с контрольной', async () => {
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код', quiz: null },
      }),
    )
    fetchMock.mockResolvedValue(apiReply(twoModules))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    const [first, second] = wrapper.findAll('.map__module')
    expect(first!.classes()).toContain('map__module--done')
    expect(first!.get('.map__module-head .map__badge').text()).toBe('Модуль пройден')
    expect(first!.get('.map__module-progress').text()).toBe('Пройдено уроков: 2 из 2')
    expect(first!.attributes('aria-labelledby')).toBe('module-01-first-programs')
    expect(first!.get('#module-01-first-programs').text()).toBe('Первые программы')
    expect(second!.classes()).not.toContain('map__module--done')
    expect(second!.find('.map__module-head .map__badge').exists()).toBe(false)
    expect(second!.get('.map__module-progress').text()).toBe('Пройдено уроков: 0 из 2')
    // «Продолжить» — в первый непройденный урок, уже следующего модуля
    expect(wrapper.get('.map__cta').text()).toBe('Продолжить: Цикл while')
    expect(wrapper.get('.map__cta').attributes('href')).toBe('/courses/csharp/02-loops/01-while')
    expect(wrapper.get('.map__count').text()).toBe('2 / 4')
  })

  it('контрольная отмечена бейджем «Контрольная», а не «Урок N»', async () => {
    fetchMock.mockResolvedValue(apiReply(twoModules))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    expect(
      wrapper
        .findAll('.map__lesson')
        .map((card) => [
          card.findAll('.map__badge').map((badge) => badge.text()),
          card.get('.map__lesson-title').text(),
        ]),
    ).toEqual([
      [['Сейчас'], '1. Первая программа'],
      [['Контрольная'], 'Контрольная: гоблин'],
      [['Урок 1'], '1. Цикл while'],
      [['Контрольная'], 'Контрольная: подземелье'],
    ])
  })

  it('пройденная контрольная — и «Пройден», и «Контрольная»; «Продолжить» — в первый непройденный урок', async () => {
    // Пройдены контрольная первого модуля и урок второго, а первый урок — нет
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/02-module-test': { goblin: 'код', quiz: null },
        '02-loops/01-while': { quiz: null },
      }),
    )
    fetchMock.mockResolvedValue(apiReply(twoModules))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    const cards = wrapper.findAll('.map__lesson')
    expect(cards[1]!.findAll('.map__badge').map((badge) => badge.text())).toEqual([
      'Пройден',
      'Контрольная',
    ])
    // Контрольная пройдена, но урок модуля — нет: модуль не пройден
    expect(wrapper.find('.map__module--done').exists()).toBe(false)
    expect(wrapper.findAll('.map__module-progress').map((item) => item.text())).toEqual([
      'Пройдено уроков: 1 из 2',
      'Пройдено уроков: 1 из 2',
    ])
    expect(wrapper.get('.map__cta').text()).toBe('Продолжить: Первая программа')
    expect(wrapper.get('[aria-current="step"]').attributes('href')).toBe(
      '/courses/csharp/01-first-programs/01-hello',
    )
  })

  it('все модули пройдены — «Модуль пройден» у каждого, курс закончен', async () => {
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код', quiz: null },
        '02-loops/01-while': { quiz: null },
        '02-loops/02-module-test': { quiz: null },
      }),
    )
    fetchMock.mockResolvedValue(apiReply(twoModules))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [createPinia(), await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    expect(wrapper.findAll('.map__module--done')).toHaveLength(2)
    expect(wrapper.find('.map__cta').exists()).toBe(false)
    expect(wrapper.get('.map__finished').text()).toBe('Все уроки пройдены — ты молодец!')
  })
})
