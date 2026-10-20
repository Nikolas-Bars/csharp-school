import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import LessonView from '@/views/LessonView.vue'

import { apiReply, lesson } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

vi.mock('@/composables/useRunner', () => ({
  useRunner: () => ({
    preload: vi.fn<() => void>(),
    test: vi.fn<() => Promise<never>>(),
    // Компиляция «в фоне» не завершается: в этих тестах она не нужна
    compile: vi.fn<() => Promise<never>>(() => new Promise(() => {})),
  }),
}))
vi.mock('@/components/lesson/CodeEditor.vue', () => ({
  __esModule: true,
  default: { template: '<textarea />' },
}))

async function mountLesson(path = '/courses/csharp/01-first-programs/01-hello') {
  const router = await testRouter(path)
  const wrapper = mount(LessonView, { global: { plugins: [createPinia(), router] } })
  await flushPromises()
  return { wrapper, router }
}

describe('LessonView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    localStorage.clear()
    fetchMock.mockImplementation(async () => apiReply(lesson))
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('загружает урок по адресу и начинает с первого шага', async () => {
    const { wrapper } = await mountLesson()

    expect(String(fetchMock.mock.calls[0]?.[0])).toMatch(
      /\/api\/courses\/csharp\/modules\/01-first-programs\/lessons\/01-hello$/,
    )
    expect(wrapper.get('h1').text()).toBe('Первая программа')
    expect(wrapper.text()).toContain('Шаг 1 из 3')
    expect(wrapper.text()).toContain('Что такое программа')
  })

  it('«Дальше» переводит на следующий шаг через адрес и отмечает пройденный', async () => {
    const { wrapper, router } = await mountLesson()

    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Дальше')!
      .trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.query.step).toBe('2')
    // Смена шага — тот же урок: повторной загрузки нет
    expect(fetchMock).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Привет, мир!')
    expect(wrapper.find('.lesson__step--done').exists()).toBe(true)
  })

  it('шаг из адреса; полоски шагов — кнопки с понятной подписью', async () => {
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/01-hello?step=3')

    expect(wrapper.text()).toContain('Проверь себя')
    expect(
      wrapper.findAll('.lesson__step').map((button) => button.attributes('aria-label')),
    ).toEqual(['Шаг 1: теория', 'Шаг 2: задача', 'Шаг 3: проверка знаний'])
    expect(wrapper.get('[aria-current="step"]').attributes('aria-label')).toBe(
      'Шаг 3: проверка знаний',
    )
  })

  it('шаг за пределами урока — ближайший существующий', async () => {
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/01-hello?step=99')

    expect(wrapper.text()).toContain('Шаг 3 из 3')
  })

  it('проверка знаний пройдена — шаг засчитан в прогресс гостя', async () => {
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/01-hello?step=3')

    await wrapper.get('input[type="radio"]').setValue(true)
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Проверить ответы')!
      .trigger('click')
    await flushPromises()

    expect(JSON.parse(localStorage.getItem('progress:csharp') ?? '{}')).toEqual({
      '01-first-programs/01-hello': { quiz: null },
    })
    expect(wrapper.findAll('.lesson__step--done')).toHaveLength(1)
  })

  it('решение задачи из прогресса гостя подставляется в редактор', async () => {
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'Console.WriteLine("Привет, мир!");' },
      }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/01-hello?step=2')

    expect(wrapper.findComponent({ name: 'LessonTask' }).props('solution')).toBe(
      'Console.WriteLine("Привет, мир!");',
    )
    // Задача уже решена — полоска шага отмечена
    expect(wrapper.findAll('.lesson__step')[1]!.classes()).toContain('lesson__step--done')
  })
})
