import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import HomeView from '@/views/HomeView.vue'

import { apiReply, csharpSummary, englishSummary } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

enableAutoUnmount(afterEach)

async function mountHome() {
  const wrapper = mount(HomeView, { global: { plugins: [createPinia(), await testRouter()] } })
  await flushPromises()
  return wrapper
}

describe('HomeView', () => {
  beforeEach(() => vi.stubGlobal('fetch', fetchMock))
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('выбор курса: карточки по порядку с сервера, у каждой — первый урок и программа', async () => {
    fetchMock.mockImplementation(async () =>
      apiReply({ items: [englishSummary, csharpSummary], next_cursor: null }),
    )
    const wrapper = await mountHome()

    expect(wrapper.find('h1').text()).toBe('Чему учимся сегодня?')
    expect(wrapper.findAll('article h2').map((title) => title.text())).toEqual([
      'Английский: все времена',
      'C# с нуля до первой игры',
    ])
    expect(wrapper.findAll('article a').map((link) => link.attributes('href'))).toEqual([
      '/courses/english/01-simple-tenses/01-present-simple',
      '/courses/english',
      '/courses/csharp/01-first-programs/01-hello',
      '/courses/csharp',
    ])
  })

  it('пока список грузится — «Загружаем курсы…»', async () => {
    fetchMock.mockImplementation(() => new Promise(() => {}))
    const wrapper = mount(HomeView, { global: { plugins: [createPinia(), await testRouter()] } })

    expect(wrapper.get('[role="status"]').text()).toBe('Загружаем курсы…')
  })

  it('не загрузилось — текст ошибки с сервера и «Попробовать ещё раз»', async () => {
    fetchMock.mockResolvedValue(
      new Response(
        JSON.stringify({ status: 'error', message: 'Сервер недоступен', data: null, errors: {} }),
        {
          status: 500,
        },
      ),
    )
    const wrapper = await mountHome()

    expect(wrapper.get('[role="alert"]').text()).toContain('Сервер недоступен')
    expect(wrapper.get('[role="alert"] button').text()).toBe('Попробовать ещё раз')
  })

  it('курсов нет — так и сказано', async () => {
    fetchMock.mockImplementation(async () => apiReply({ items: [], next_cursor: null }))
    const wrapper = await mountHome()

    expect(wrapper.text()).toContain('Курсов пока нет.')
    expect(wrapper.find('article').exists()).toBe(false)
  })
})
