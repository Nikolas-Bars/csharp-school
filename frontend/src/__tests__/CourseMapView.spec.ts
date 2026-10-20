import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import CourseMapView from '@/views/CourseMapView.vue'

import { apiReply, course } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

describe('CourseMapView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
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
})
