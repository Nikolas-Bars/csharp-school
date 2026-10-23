import { flushPromises, mount, type VueWrapper } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import ExerciseCard from '@/components/exercise/ExerciseCard.vue'
import type { Exercise } from '@/types/api'

import {
  choiceExercise,
  fixExercise,
  gapExercise,
  orderExercise,
  translateExercise,
} from './lessonFixtures'

const mountCard = (exercise: Exercise) => mount(ExerciseCard, { props: { exercise } })

const button = (wrapper: VueWrapper, text: string) =>
  wrapper.findAll('button').find((item) => item.text() === text)

async function check(wrapper: VueWrapper) {
  await wrapper.get('form').trigger('submit')
}

const feedback = (wrapper: VueWrapper) => wrapper.get('[aria-live]').text()

describe('ExerciseCard', () => {
  it('выбор: пока ничего не выбрано — проверять нечего', () => {
    const wrapper = mountCard(choiceExercise)

    expect(wrapper.get('h2').text()).toContain('Как сказать «Я сейчас читаю»?')
    expect(button(wrapper, 'Проверить')!.attributes('disabled')).toBeDefined()
    expect(wrapper.findAll('input[type="radio"]')).toHaveLength(3)
  })

  it('выбор: неверный вариант — его объяснение, верный — «Верно!» и «Дальше»', async () => {
    const wrapper = mountCard(choiceExercise)
    const radios = wrapper.findAll('input[type="radio"]')

    await radios[0]!.setValue(true)
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Пока не так')
    expect(feedback(wrapper)).toContain('Это Present Simple — про «обычно».')
    expect(wrapper.text()).toContain('Попытка 2')

    // Ответ поменяли — ошибка уходит до новой проверки
    await radios[1]!.setValue(true)
    expect(feedback(wrapper)).toBe('')

    await check(wrapper)
    expect(feedback(wrapper)).toContain('Верно!')
    expect(feedback(wrapper)).toContain('I → am, к глаголу -ing.')

    await button(wrapper, 'Дальше')!.trigger('click')
    expect(wrapper.emitted('next')).toHaveLength(1)
  })

  it('после второй неудачи — правильный ответ, его нужно выбрать самому', async () => {
    const wrapper = mountCard(choiceExercise)
    const radios = wrapper.findAll('input[type="radio"]')

    await radios[0]!.setValue(true)
    await check(wrapper)
    await radios[2]!.setValue(true)
    await check(wrapper)

    expect(feedback(wrapper)).toContain('Правильный ответ: I am reading now.')
    expect(feedback(wrapper)).toContain('Пропущен am.')
    expect(feedback(wrapper)).toContain('Выбери его, чтобы идти дальше.')
    expect(wrapper.findAll('.choice__option')[1]!.classes()).toContain('choice__option--shown')
    expect(button(wrapper, 'Дальше')).toBeUndefined()

    await radios[1]!.setValue(true)
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Верно!')
  })

  it('пропуск: частая ошибка объясняется, другой неверный ответ — подсказка', async () => {
    const wrapper = mountCard(gapExercise)
    const input = wrapper.get('input')

    expect(wrapper.get('.gap').text()).toContain('She')
    expect(input.attributes('aria-label')).toBe('Пропуск во фразе: She ___ (cook) dinner now.')

    await input.setValue('cooks')
    await check(wrapper)
    expect(feedback(wrapper)).toContain('now — сейчас, нужен Continuous.')

    await input.setValue('cooking')
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Правильный ответ: is cooking')
    expect(feedback(wrapper)).toContain('Подсказка без ответа.')

    await input.setValue("'s cooking")
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Верно!')
    expect(input.attributes('readonly')).toBeDefined()
  })

  it('собери фразу: слово вверх и обратно, проверка порядка', async () => {
    const wrapper = mountCard(orderExercise)
    const pool = () => wrapper.findAll('.order__pool button').map((item) => item.text())
    const put = (word: string) =>
      wrapper
        .findAll('.order__pool button')
        .find((item) => item.text() === word)!
        .trigger('click')

    await put('What')
    await put('do')
    expect(pool()).toEqual(['doing', 'you', 'are'])

    // Слово во фразе возвращается вниз
    await wrapper.get('.order__line button').trigger('click')
    expect(pool()).toEqual(['doing', 'you', 'What', 'are'])
    await wrapper.get('.order__line button').trigger('click')
    expect(wrapper.text()).toContain('Нажимай на слова по порядку.')

    for (const word of ['What', 'do', 'you', 'doing']) {
      await put(word)
    }
    await check(wrapper)
    expect(feedback(wrapper)).toContain('do тут лишнее.')

    for (let i = 0; i < 4; i++) {
      await wrapper.get('.order__line button').trigger('click')
    }
    for (const word of ['What', 'are', 'you', 'doing']) {
      await put(word)
    }
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Верно!')
    expect(wrapper.get('.order__pool button').attributes('disabled')).toBeDefined()
  })

  it('перевод: краткие формы засчитываются', async () => {
    const wrapper = mountCard(translateExercise)

    expect(wrapper.text()).toContain('«Я работаю.»')
    expect(wrapper.text()).toContain('Краткие формы тоже засчитываются')
    await wrapper.get('textarea').setValue("I'm working.")
    await check(wrapper)

    expect(feedback(wrapper)).toContain('Верно!')
  })

  it('найди ошибку: сначала слово, потом исправление', async () => {
    const wrapper = mountCard(fixExercise)
    const words = () => wrapper.findAll('.fix__word')

    expect(words().map((item) => item.text())).toEqual(['Look!', 'She', 'are', 'waiting.'])
    expect(wrapper.find('input').exists()).toBe(false)

    await words()[1]!.trigger('click')
    expect(words()[1]!.attributes('aria-pressed')).toBe('true')
    await wrapper.get('input').setValue('he')
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Это слово стоит верно.')

    await words()[2]!.trigger('click')
    expect(wrapper.get('label').text()).toBe('Как правильно вместо «are»?')
    // Выбрали другое слово — исправление начинается заново
    expect((wrapper.get('input').element as HTMLInputElement).value).toBe('')
    await wrapper.get('input').setValue('is')
    await check(wrapper)
    expect(feedback(wrapper)).toContain('Верно!')
  })

  it('тот же неверный ответ второй раз не проверяется — случайный двойной Enter не показывает ответ', async () => {
    const wrapper = mountCard(gapExercise)

    await wrapper.get('input').setValue('cooks')
    await check(wrapper)
    await check(wrapper)

    expect(wrapper.text()).toContain('Попытка 2')
    expect(feedback(wrapper)).not.toContain('Правильный ответ')
    expect(button(wrapper, 'Проверить')!.attributes('disabled')).toBeDefined()
  })

  it('фокус: на задание при переходе с прошлого упражнения, на «Дальше» после верного ответа', async () => {
    const wrapper = mount(ExerciseCard, {
      props: { exercise: gapExercise, focusOnMount: true },
      attachTo: document.body,
    })
    expect(document.activeElement).toBe(wrapper.get('h2').element)

    await wrapper.get('input').setValue('is cooking')
    await check(wrapper)
    await flushPromises()

    expect(document.activeElement).toBe(button(wrapper, 'Дальше')!.element)
    wrapper.unmount()
  })

  it('собери фразу: после нажатия фокус — на соседнюю плитку, ряд опустел — в другой ряд', async () => {
    const wrapper = mount(ExerciseCard, {
      props: { exercise: { ...orderExercise, tiles: ['What', 'are'] } },
      attachTo: document.body,
    })
    const focused = () => (document.activeElement as HTMLElement).textContent?.trim()

    await wrapper.get('.order__pool button').trigger('click')
    await flushPromises()
    expect(focused()).toBe('are')

    await wrapper.get('.order__pool button').trigger('click')
    await flushPromises()
    // Внизу плиток не осталось — фокус на последнюю во фразе
    expect(focused()).toBe('are')
    expect(document.activeElement!.closest('.order__line')).not.toBeNull()
    wrapper.unmount()
  })
})
