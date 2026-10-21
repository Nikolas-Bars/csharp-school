import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import ModuleResult from '@/components/lesson/ModuleResult.vue'

import { testRouter } from './support/router'

const map = { name: 'course', params: { course: 'csharp' } }
const lessonAt = (module: string, lesson: string, step?: number) => ({
  name: 'lesson',
  params: { course: 'csharp', module, lesson },
  query: step ? { step } : {},
})

async function mountResult(props: InstanceType<typeof ModuleResult>['$props'], attach = false) {
  return mount(ModuleResult, {
    props,
    attachTo: attach ? document.body : undefined,
    global: { plugins: [await testRouter('/')] },
  })
}

const links = (wrapper: Awaited<ReturnType<typeof mountResult>>) =>
  wrapper.findAll('a').map((link) => [link.text(), link.attributes('href'), link.classes()[1]])

describe('ModuleResult', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('модуль пройден — поздравление и следующий модуль, карта — второй ссылкой', async () => {
    const wrapper = await mountResult({
      module: 'Первые программы',
      left: 0,
      next: { title: 'Циклы', to: lessonAt('02-loops', '01-while') },
      map,
    })

    expect(wrapper.classes()).toContain('result--done')
    expect(wrapper.get('h2').text()).toBe('Модуль пройден!')
    expect(wrapper.text()).toContain(
      'Все уроки модуля «Первые программы» и контрольная пройдены. Отличная работа!',
    )
    expect(wrapper.text()).not.toContain('курс пройден')
    expect(links(wrapper)).toEqual([
      ['Следующий модуль: Циклы', '/courses/csharp/02-loops/01-while', 'result__link--primary'],
      ['К карте курса', '/courses/csharp', 'result__link--secondary'],
    ])
  })

  it('пройден весь курс — поздравление, главная ссылка — на карту', async () => {
    const wrapper = await mountResult({ module: 'Методы', left: 0, courseDone: true, map })

    expect(wrapper.text()).toContain('Все модули пройдены — курс пройден целиком!')
    expect(links(wrapper)).toEqual([['К карте курса', '/courses/csharp', 'result__link--primary']])
  })

  it('последний модуль пройден, а в курсе есть непройденное — первый непройденный урок', async () => {
    const wrapper = await mountResult({
      module: 'Методы',
      left: 0,
      resume: { title: 'Переменные', to: lessonAt('01-first-programs', '02-variables') },
      map,
    })

    expect(wrapper.get('h2').text()).toBe('Модуль пройден!')
    expect(wrapper.text()).not.toContain('курс пройден')
    expect(wrapper.text()).toContain('В курсе остались непройденные уроки')
    expect(links(wrapper)[0]).toEqual([
      'Продолжить: Переменные',
      '/courses/csharp/01-first-programs/02-variables',
      'result__link--primary',
    ])
  })

  it('остались уроки и задачи контрольной — сколько, урок главной ссылкой, задачи второй', async () => {
    const wrapper = await mountResult({
      module: 'Первые программы',
      left: 2,
      next: { title: 'Циклы', to: lessonAt('02-loops', '01-while') },
      resume: { title: 'Переменные', to: lessonAt('01-first-programs', '02-variables') },
      tasks: lessonAt('01-first-programs', '12-module-test', 2),
      map,
    })

    expect(wrapper.classes()).not.toContain('result--done')
    expect(wrapper.get('h2').text()).toBe('Вопросы контрольной пройдены!')
    expect(wrapper.text()).toContain('Осталось уроков: 2.')
    // Следующий модуль не предлагается, пока этот не пройден
    expect(links(wrapper)).toEqual([
      [
        'Продолжить: Переменные',
        '/courses/csharp/01-first-programs/02-variables',
        'result__link--primary',
      ],
      [
        'Решить задачи контрольной',
        '/courses/csharp/01-first-programs/12-module-test?step=2',
        'result__link--secondary',
      ],
      ['К карте курса', '/courses/csharp', 'result__link--secondary'],
    ])
  })

  it('не решены только задачи контрольной — ссылка на них главной', async () => {
    const wrapper = await mountResult({
      module: 'Первые программы',
      left: 1,
      tasks: lessonAt('01-first-programs', '12-module-test', 2),
      map,
    })

    expect(links(wrapper)[0]).toEqual([
      'Решить задачи контрольной',
      '/courses/csharp/01-first-programs/12-module-test?step=2',
      'result__link--primary',
    ])
  })

  it('появился — фокус на заголовке итога, отдельного живого региона нет', async () => {
    const wrapper = await mountResult({ module: 'Методы', left: 0, courseDone: true, map }, true)

    expect(document.activeElement).toBe(wrapper.get('h2').element)
    expect(wrapper.get('h2').attributes('tabindex')).toBe('-1')
    expect(wrapper.attributes('aria-labelledby')).toBe(wrapper.get('h2').attributes('id'))
    expect(wrapper.find('[role="status"]').exists()).toBe(false)
    wrapper.unmount()
  })
})
