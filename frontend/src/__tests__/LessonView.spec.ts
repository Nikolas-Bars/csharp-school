import { enableAutoUnmount, flushPromises, mount, type VueWrapper } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import { DEFAULT_LOCALE, setLocale } from '@/i18n/locale'
import { useAuthStore } from '@/stores/auth'
import type { Course, Lesson } from '@/types/api'
import LessonView from '@/views/LessonView.vue'

import { apiReply, course, lesson, moduleTest, twoModules } from './lessonFixtures'
import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

// Смонтированные страницы следят за языком: после теста — размонтировать
enableAutoUnmount(afterEach)

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

/** Ответы по адресу: карта курса, уроки и (other) остальное */
function serve(
  map: Course,
  lessons: Record<string, Lesson> = {},
  other: (url: string) => Response | null = () => null,
) {
  fetchMock.mockImplementation(async (input) => {
    const url = String(input)
    const answer = other(url)
    if (answer) {
      return answer
    }
    if (url.endsWith('/courses/csharp')) {
      return apiReply(map)
    }
    const found = Object.entries(lessons).find(([path]) => url.endsWith(path))
    return apiReply(found?.[1] ?? lesson)
  })
}

const mapRequests = () =>
  fetchMock.mock.calls.filter(([url]) => String(url).endsWith('/courses/csharp')).length

/** Ссылки итога: текст и адрес */
const links = (wrapper: Pick<VueWrapper, 'findAll'>) =>
  wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')])

/** Контрольная последнего модуля twoModules: только вопросы */
const quizOnlyTest: Lesson = {
  ...lesson,
  slug: '02-module-test',
  module: { slug: '02-loops', title: 'Циклы' },
  steps: [lesson.steps[2]!],
}

const lessonRequests = () =>
  fetchMock.mock.calls.filter(([url]) => String(url).includes('/lessons/')).length

/** Ответить на все вопросы проверки знаний верно (в фикстурах верный — первый вариант) */
async function passQuiz(wrapper: ReturnType<typeof mount>) {
  await wrapper.get('input[type="radio"]').setValue(true)
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Проверить ответы')!
    .trigger('click')
  await flushPromises()
}

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
    tokenStorage.clear()
    serve(course)
  })
  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
    setLocale(DEFAULT_LOCALE)
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
    expect(lessonRequests()).toBe(1)
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

  it('обычный урок, задачи решены: после вопросов — «урок пройден» и ссылка на карту', async () => {
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ '01-first-programs/01-hello': { 'hello-world': 'код' } }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/01-hello?step=3')

    await passQuiz(wrapper)

    expect(wrapper.get('.quiz__done strong').text()).toBe('Всё верно — урок пройден!')
    expect(wrapper.findComponent({ name: 'ModuleResult' }).exists()).toBe(false)
    expect(wrapper.findAll('.lesson__done-links a').map((link) => link.text())).toEqual([
      'К карте курса',
    ])
    expect(wrapper.get('.lesson__map-link').attributes('href')).toBe('/courses/csharp')
  })

  it('обычный урок, задача не решена: честный текст и ссылка на первую нерешённую задачу', async () => {
    const { wrapper, router } = await mountLesson(
      '/courses/csharp/01-first-programs/01-hello?step=3',
    )

    await passQuiz(wrapper)

    expect(wrapper.get('.quiz__done strong').text()).toBe(
      'Все ответы верные. Осталось решить задачи урока: 1',
    )
    expect(wrapper.text()).not.toContain('урок пройден')
    expect(
      wrapper
        .findAll('.lesson__done-links a')
        .map((link) => [link.text(), link.attributes('href')]),
    ).toEqual([
      ['Перейти к задаче: Привет, мир!', '/courses/csharp/01-first-programs/01-hello?step=2'],
      ['К карте курса', '/courses/csharp'],
    ])

    await wrapper.get('.lesson__done-link--primary').trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.query.step).toBe('2')
  })

  it('контрольная пройдена и все уроки модуля тоже — «Модуль пройден!» и следующий модуль', async () => {
    serve(twoModules, { '/02-module-test': moduleTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код' },
      }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    expect(wrapper.text()).not.toContain('Модуль пройден!')
    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.get('h2').text()).toBe('Модуль пройден!')
    expect(result.text()).toContain(
      'Все уроки модуля «Первые программы» и контрольная пройдены. Отличная работа!',
    )
    expect(links(result)).toEqual([
      ['Следующий модуль: Циклы', '/courses/csharp/02-loops/01-while'],
      ['К карте курса', '/courses/csharp'],
    ])
    // Карта курса загружена один раз, урок — один раз
    expect(mapRequests()).toBe(1)
    expect(lessonRequests()).toBe(1)
  })

  it('следующий модуль — с его первого непройденного урока', async () => {
    serve(twoModules, { '/02-module-test': moduleTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код' },
        '02-loops/01-while': { quiz: null },
      }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    await passQuiz(wrapper)

    expect(links(wrapper.getComponent({ name: 'ModuleResult' }))[0]).toEqual([
      'Следующий модуль: Циклы',
      '/courses/csharp/02-loops/02-module-test',
    ])
  })

  it('пустой следующий модуль пропускается, а не считается концом курса', async () => {
    const withEmpty: Course = {
      ...twoModules,
      modules: [
        twoModules.modules[0]!,
        { slug: '02-empty', title: 'Пусто', description: '', lessons: [] },
        twoModules.modules[1]!,
      ],
    }
    serve(withEmpty, { '/02-module-test': moduleTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код' },
      }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.text()).not.toContain('курс пройден')
    expect(links(result)[0]).toEqual([
      'Следующий модуль: Циклы',
      '/courses/csharp/02-loops/01-while',
    ])
  })

  it('пройден весь курс — «курс пройден целиком», главная ссылка — на карту', async () => {
    serve(twoModules, { '/02-module-test': quizOnlyTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код', quiz: null },
        '02-loops/01-while': { quiz: null },
      }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/02-loops/02-module-test')

    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.text()).toContain('Все модули пройдены — курс пройден целиком!')
    expect(result.findAll('a').map((link) => link.text())).toEqual(['К карте курса'])
    expect(result.get('a').classes()).toContain('result__link--primary')
  })

  it('последний модуль пройден, а прошлые нет — не «курс пройден», а первый непройденный урок', async () => {
    serve(twoModules, { '/02-module-test': quizOnlyTest })
    localStorage.setItem('progress:csharp', JSON.stringify({ '02-loops/01-while': { quiz: null } }))
    const { wrapper } = await mountLesson('/courses/csharp/02-loops/02-module-test')

    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.get('h2').text()).toBe('Модуль пройден!')
    expect(result.text()).not.toContain('курс пройден')
    expect(result.text()).toContain('В курсе остались непройденные уроки')
    expect(links(result)).toEqual([
      ['Продолжить: Первая программа', '/courses/csharp/01-first-programs/01-hello'],
      ['К карте курса', '/courses/csharp'],
    ])
  })

  it('вопросы контрольной пройдены, а урок модуля — нет: сколько осталось и куда идти', async () => {
    serve(twoModules, { '/02-module-test': moduleTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ '01-first-programs/02-module-test': { goblin: 'код' } }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.get('h2').text()).toBe('Вопросы контрольной пройдены!')
    expect(result.text()).toContain('Осталось уроков: 1.')
    expect(links(result)).toEqual([
      ['Продолжить: Первая программа', '/courses/csharp/01-first-programs/01-hello'],
      ['К карте курса', '/courses/csharp'],
    ])
  })

  it('вопросы контрольной пройдены, а её задача — нет: ссылка на задачу', async () => {
    serve(twoModules, { '/02-module-test': moduleTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null } }),
    )
    const { wrapper, router } = await mountLesson(
      '/courses/csharp/01-first-programs/02-module-test?step=2',
    )

    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.text()).toContain('Осталось уроков: 1.')
    expect(links(result)).toEqual([
      ['Решить задачи контрольной', '/courses/csharp/01-first-programs/02-module-test?step=1'],
      ['К карте курса', '/courses/csharp'],
    ])
    await result.get('a').trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.query.step).toBe('1')
  })

  it('не пройдены ни прошлый урок, ни задачи контрольной — обе ссылки', async () => {
    serve(twoModules, { '/02-module-test': moduleTest })
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    await passQuiz(wrapper)

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.text()).toContain('Осталось уроков: 2.')
    expect(links(result)).toEqual([
      ['Продолжить: Первая программа', '/courses/csharp/01-first-programs/01-hello'],
      ['Решить задачи контрольной', '/courses/csharp/01-first-programs/02-module-test?step=1'],
      ['К карте курса', '/courses/csharp'],
    ])
  })

  it('устаревшая карта (id задач сменились) — итог по настоящим шагам урока', async () => {
    // В памяти — карта до course:import: у контрольной была задача old-task
    const stale: Course = {
      ...twoModules,
      modules: [
        {
          ...twoModules.modules[0]!,
          lessons: [
            twoModules.modules[0]!.lessons[0]!,
            { ...twoModules.modules[0]!.lessons[1]!, steps: ['old-task', 'quiz'] },
          ],
        },
        twoModules.modules[1]!,
      ],
    }
    serve(stale, { '/02-module-test': moduleTest })
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({
        '01-first-programs/01-hello': { 'hello-world': 'код', quiz: null },
        '01-first-programs/02-module-test': { goblin: 'код' },
      }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    await passQuiz(wrapper)

    // Не «Решить задачи контрольной» с шагом 1 по кругу: всё решено — модуль пройден
    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.get('h2').text()).toBe('Модуль пройден!')
    expect(result.text()).not.toContain('Решить задачи')
  })

  it('смена языка — карта курса загружается заново на новом языке', async () => {
    serve(twoModules, { '/02-module-test': moduleTest })
    await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')
    expect(mapRequests()).toBe(1)

    setLocale('en')
    await flushPromises()

    const maps = fetchMock.mock.calls.filter(([url]) => String(url).endsWith('/courses/csharp'))
    expect(maps).toHaveLength(2)
    expect(new Headers(maps[1]?.[1]?.headers).get('Accept-Language')).toBe('en')
  })

  it('вход ученика на экране итога — итог пересчитывается по прогрессу аккаунта', async () => {
    const account = { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'ru' }
    const saved = {
      lessons: [
        {
          module: '01-first-programs',
          lesson: '01-hello',
          steps: ['hello-world', 'quiz'],
          completed: ['hello-world', 'quiz'],
          done: true,
        },
        {
          module: '01-first-programs',
          lesson: '02-module-test',
          steps: ['goblin', 'quiz'],
          completed: ['goblin', 'quiz'],
          done: true,
        },
      ],
    }
    serve(twoModules, { '/02-module-test': moduleTest }, (url) =>
      url.endsWith('/auth/login')
        ? apiReply({ user: account, token: '1|t' })
        : url.includes('/progress/')
          ? apiReply(saved)
          : null,
    )
    // Гость решил задачу контрольной, а первый урок прошёл раньше с аккаунтом
    localStorage.setItem(
      'progress:csharp',
      JSON.stringify({ '01-first-programs/02-module-test': { goblin: 'код' } }),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')
    await passQuiz(wrapper)
    expect(wrapper.getComponent({ name: 'ModuleResult' }).get('h2').text()).toBe(
      'Вопросы контрольной пройдены!',
    )

    await useAuthStore().login({ email: account.email, password: 'Password123' })
    await flushPromises()

    const result = wrapper.getComponent({ name: 'ModuleResult' })
    expect(result.get('h2').text()).toBe('Модуль пройден!')
    expect(links(result)[0]).toEqual([
      'Следующий модуль: Циклы',
      '/courses/csharp/02-loops/01-while',
    ])
  })

  it('карта курса не загрузилась — урок работает, после вопросов — ссылка на карту', async () => {
    fetchMock.mockImplementation(async (input) =>
      String(input).endsWith('/courses/csharp')
        ? new Response(
            JSON.stringify({ status: 'error', message: 'Сбой', data: null, errors: {} }),
            {
              status: 500,
            },
          )
        : apiReply(moduleTest),
    )
    const { wrapper } = await mountLesson('/courses/csharp/01-first-programs/02-module-test?step=2')

    await passQuiz(wrapper)

    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
    expect(wrapper.findComponent({ name: 'ModuleResult' }).exists()).toBe(false)
    expect(wrapper.findAll('.lesson__done-links a').map((link) => link.text())).toEqual([
      'Перейти к задаче: Бой с гоблином',
      'К карте курса',
    ])
  })
})
