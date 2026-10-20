import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import CourseMapView from '@/views/CourseMapView.vue'

import { apiReply, course } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

describe('CourseMapView', () => {
  beforeEach(() => vi.stubGlobal('fetch', fetchMock))
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('модули и уроки по порядку, со ссылками и числом задач', async () => {
    fetchMock.mockResolvedValue(apiReply(course))
    const wrapper = mount(CourseMapView, {
      global: { plugins: [await testRouter('/courses/csharp')] },
    })
    await flushPromises()

    expect(String(fetchMock.mock.calls[0]?.[0])).toMatch(/\/api\/courses\/csharp$/)
    expect(wrapper.find('h1').text()).toBe('Первые программы')
    expect(
      wrapper.findAll('.map__lesson').map((link) => [link.text(), link.attributes('href')]),
    ).toEqual([
      ['Урок 12 задачи1. Первая программа', '/courses/csharp/01-first-programs/01-hello'],
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
      global: { plugins: [await testRouter('/courses/nope')] },
    })
    await flushPromises()

    expect(wrapper.get('[role="alert"]').text()).toContain('Такого курса нет.')
  })
})
