import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import ReviewView from '@/views/ReviewView.vue'
import VocabularyView from '@/views/VocabularyView.vue'

import { apiReply, courseWords, wordsCourse } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

enableAutoUnmount(afterEach)

/** Гость: карта курса и карточки с сервера, словарь — в браузере */
function serve() {
  fetchMock.mockImplementation(async (input) => {
    const url = String(input)
    if (url.includes('/courses/english/words')) {
      return apiReply({ items: courseWords, next_cursor: null })
    }
    return apiReply(wordsCourse)
  })
}

const learned = (box: number, dueAt: string) => ({ box, due_at: dueAt, reviewed_at: null })

async function mountView(view: typeof VocabularyView, path: string) {
  const router = await testRouter(path)
  const wrapper = mount(view, { global: { plugins: [createPinia(), router] } })
  await flushPromises()
  return { wrapper, router }
}

describe('VocabularyView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
    tokenStorage.clear()
    serve()
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('пустой словарь — куда идти за словами', async () => {
    const { wrapper } = await mountView(VocabularyView, '/courses/english/words')

    expect(wrapper.get('h1').text()).toBe('Мой словарь')
    expect(wrapper.text()).toContain('Словарь пока пуст')
    expect(wrapper.text()).toContain('Словарь хранится в этом браузере')
  })

  it('слова словаря: сколько ждут сегодня, ссылка на повторение', async () => {
    localStorage.setItem(
      'vocabulary:english',
      JSON.stringify({
        wear: learned(0, new Date().toISOString()),
        'at-the-moment': learned(1, new Date(Date.now() + 3 * 86_400_000).toISOString()),
      }),
    )
    const { wrapper } = await mountView(VocabularyView, '/courses/english/words')

    expect(wrapper.findAll('.list__word strong').map((item) => item.text())).toEqual([
      'wear',
      'at the moment',
    ])
    expect(wrapper.text()).toContain('глагол · Модуль 1 · Урок 1')
    expect(wrapper.get('.dictionary__due-count strong').text()).toBe('1')
    expect(wrapper.get('.dictionary__due-count span').text()).toBe('слово ждёт сегодня')
    expect(wrapper.get('.dictionary__cta').attributes('href')).toBe('/courses/english/words/review')
  })

  it('слова уроков, пройденных раньше, попадают в словарь сами', async () => {
    localStorage.setItem(
      'progress:english',
      JSON.stringify({ '01-present/01-right-now': { form: null, quiz: null } }),
    )
    const { wrapper } = await mountView(VocabularyView, '/courses/english/words')

    expect(wrapper.findAll('.list__word strong')).toHaveLength(2)
    expect(Object.keys(JSON.parse(localStorage.getItem('vocabulary:english')!) as object)).toEqual([
      'wear',
      'at-the-moment',
    ])
  })
})

describe('ReviewView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
    tokenStorage.clear()
    serve()
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('повторяются слова, которые ждут сегодня; ответ меняет расписание', async () => {
    localStorage.setItem(
      'vocabulary:english',
      JSON.stringify({
        wear: learned(0, new Date().toISOString()),
        'at-the-moment': learned(1, new Date(Date.now() + 3 * 86_400_000).toISOString()),
      }),
    )
    const { wrapper } = await mountView(ReviewView, '/courses/english/words/review')

    expect(wrapper.get('h2').text()).toBe('wear')
    expect(wrapper.text()).toContain('1 из 1')
    await wrapper.get('.review__actions button').trigger('click')
    await wrapper.findAll('.review__actions button').slice(-1)[0]!.trigger('click')

    expect(wrapper.text()).toContain('Повторение закончено')
    const saved = JSON.parse(localStorage.getItem('vocabulary:english')!) as Record<
      string,
      { box: number }
    >
    expect(saved.wear?.box).toBe(1)
  })

  it('нечего повторять — так и сказано', async () => {
    const { wrapper } = await mountView(ReviewView, '/courses/english/words/review')

    expect(wrapper.text()).toContain('Сейчас повторять нечего')
  })
})
