import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import LessonQuiz from '@/components/lesson/LessonQuiz.vue'

const questions = [
  {
    question: 'Чем заканчивается команда?',
    options: [';', '.'],
    answer: 0,
    explanation: 'Точкой с запятой.',
  },
  {
    question: 'Порядок команд?',
    options: ['Снизу вверх', 'Сверху вниз'],
    answer: 1,
    explanation: 'Сверху вниз.',
  },
]

describe('LessonQuiz', () => {
  it('без всех ответов — просит ответить', async () => {
    const wrapper = mount(LessonQuiz, { props: { questions } })

    await wrapper.get('button').trigger('click')

    expect(wrapper.get('[role="alert"]').text()).toBe('Ответь на все вопросы.')
  })

  it('ошибка — объяснение и можно поменять ответ; всё верно — урок пройден', async () => {
    const wrapper = mount(LessonQuiz, {
      props: { questions },
      slots: { done: '<a href="/courses/csharp">К карте курса</a>' },
    })
    const radios = wrapper.findAll('input[type="radio"]')

    await radios[1]!.setValue(true) // неверно
    await radios[3]!.setValue(true)
    await wrapper.get('button').trigger('click')
    expect(wrapper.text()).toContain('Не совсем. Точкой с запятой.')
    expect(wrapper.emitted('done')).toBeUndefined()

    await radios[0]!.setValue(true)
    await wrapper.get('button').trigger('click')

    expect(wrapper.text()).toContain('Всё верно — урок пройден!')
    expect(wrapper.emitted('done')).toHaveLength(1)
    expect(wrapper.get('a').text()).toBe('К карте курса')
  })

  it('вопросы — группы с подписью, варианты — с меткой', () => {
    const wrapper = mount(LessonQuiz, { props: { questions } })

    expect(wrapper.findAll('fieldset legend').map((legend) => legend.text())).toEqual([
      'Вопрос 1 Чем заканчивается команда?',
      'Вопрос 2 Порядок команд?',
    ])
    expect(wrapper.findAll('label input[type="radio"]')).toHaveLength(4)
  })
})
