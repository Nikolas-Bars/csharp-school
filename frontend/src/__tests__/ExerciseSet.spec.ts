import { mount, type VueWrapper } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { h } from 'vue'

import ExerciseSet from '@/components/exercise/ExerciseSet.vue'

import { choiceExercise, gapExercise } from './lessonFixtures'

function mountSet(completed = false) {
  return mount(ExerciseSet, {
    props: { exercises: [choiceExercise, gapExercise], completed },
    slots: {
      done: ({ restart }: { restart: () => void }) =>
        h('button', { class: 'again', onClick: restart }, 'Ещё раз'),
    },
  })
}

async function solve(wrapper: VueWrapper) {
  if (wrapper.find('input[type="radio"]').exists()) {
    await wrapper.findAll('input[type="radio"]')[1]!.setValue(true)
  } else {
    await wrapper.get('input').setValue('is cooking')
  }
  await wrapper.get('form').trigger('submit')
  await wrapper
    .findAll('button')
    .find((item) => item.text() === 'Дальше')!
    .trigger('click')
}

describe('ExerciseSet', () => {
  it('упражнения по одному: вид, точки и счётчик', async () => {
    const wrapper = mountSet()

    expect(wrapper.get('.set__kind').text()).toBe('Выбери вариант')
    expect(wrapper.text()).toContain('Задание 1 из 2')
    expect(wrapper.findAll('.set__dot')).toHaveLength(2)
    expect(wrapper.findAll('.set__dot--current')).toHaveLength(1)

    await solve(wrapper)

    expect(wrapper.get('.set__kind').text()).toBe('Вставь слово')
    expect(wrapper.text()).toContain('Задание 2 из 2')
    expect(wrapper.findAll('.set__dot--done')).toHaveLength(1)
    expect(wrapper.emitted('done')).toBeUndefined()
  })

  it('после последнего — done и итог; «ещё раз» — с первого упражнения', async () => {
    const wrapper = mountSet()

    await solve(wrapper)
    await solve(wrapper)

    expect(wrapper.emitted('done')).toHaveLength(1)
    expect(wrapper.findAll('.set__dot--done')).toHaveLength(2)
    await wrapper.get('.again').trigger('click')

    expect(wrapper.text()).toContain('Задание 1 из 2')
    // Новый проход — без старых ответов
    expect(wrapper.find('input[type="radio"]:checked').exists()).toBe(false)
  })

  it('пройденный раньше набор сразу показывает итог', () => {
    const wrapper = mountSet(true)

    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.find('.again').exists()).toBe(true)
  })
})
