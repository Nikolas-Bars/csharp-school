import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { nextTick } from 'vue'

import ReviewSession from '@/components/vocabulary/ReviewSession.vue'
import WordCard from '@/components/vocabulary/WordCard.vue'
import WordList, { type WordRow } from '@/components/vocabulary/WordList.vue'
import WordSteps from '@/components/vocabulary/WordSteps.vue'

import { courseWords, momentCard, wearCard } from './lessonFixtures'

const now = new Date(2026, 9, 24, 10)
const inDays = (days: number) => new Date(now.getTime() + days * 86_400_000).toISOString()

describe('WordCard', () => {
  it('слово, транскрипция, часть речи, перевод, формы, примеры и «не путай»', () => {
    const card = mount(WordCard, { props: { card: wearCard } })

    expect(card.get('h2').text()).toBe('wear')
    expect(card.get('h2').attributes('lang')).toBe('en')
    expect(card.text()).toContain('/wɛr/')
    expect(card.text()).toContain('глагол')
    expect(card.text()).toContain('неправильный')
    expect(card.text()).toContain('носить (одежду)')
    expect(card.findAll('.word__form').map((form) => form.text())).toEqual(['wear', 'wore', 'worn'])
    expect(card.text()).toContain('Почему так: Сейчас — Present Continuous.')
    // «не путай»: **жирный** и ==маркер== без v-html
    expect(card.get('.word__compare strong').text()).toBe('Не путай с put on.')
    expect(card.get('.word__compare mark').text()).toBe('надеть')
  })

  it('без форм и «не путай» — без этих блоков; без голоса — подсказка про голос', () => {
    const card = mount(WordCard, { props: { card: momentCard } })

    expect(card.text()).toContain('фраза')
    expect(card.text()).not.toContain('неправильный')
    expect(card.find('.word__forms').exists()).toBe(false)
    expect(card.find('.word__compare').exists()).toBe(false)
    // В jsdom нет speechSynthesis — нет и английского голоса
    expect(card.text()).toContain('нет английского голоса')
    expect(card.find('button').exists()).toBe(false)
  })
})

describe('WordSteps', () => {
  it('карточки по одной, «назад» и «дальше»; после последней — done', async () => {
    const steps = mount(WordSteps, { props: { words: [wearCard, momentCard] } })
    const [prev, next] = steps.findAll('.steps__nav button')

    expect(steps.get('h2.word__word').text()).toBe('wear')
    expect(steps.text()).toContain('Слово 1 из 2')
    expect(prev!.attributes('disabled')).toBeDefined()

    await next!.trigger('click')
    expect(steps.get('h2.word__word').text()).toBe('at the moment')
    expect(next!.text()).toBe('Все слова — дальше →')

    await prev!.trigger('click')
    expect(steps.get('h2.word__word').text()).toBe('wear')

    await next!.trigger('click')
    await next!.trigger('click')
    expect(steps.emitted('done')).toHaveLength(1)
  })
})

describe('WordList', () => {
  const row = (id: string, word: string, extra: Partial<WordRow>): WordRow => ({
    id,
    word,
    ipa: '/x/',
    pos: 'verb',
    translation: `${word}-перевод`,
    lesson: 'Модуль 1 · Урок 1',
    module: 1,
    box: 0,
    dueAt: now.toISOString(),
    ...extra,
  })
  const rows = [
    row('wear', 'wear', {}),
    row('look', 'look', { box: 1, dueAt: inDays(1) }),
    row('cry', 'cry', { box: 2, dueAt: inDays(21), module: 2 }),
    row('own', 'own', { box: 4, dueAt: inDays(30), module: 2 }),
  ]
  const words = (list: ReturnType<typeof mount>) =>
    list.findAll('.list__word strong').map((item) => item.text())

  it('когда повторить: сегодня, завтра, через N дней, выучено', () => {
    const list = mount(WordList, { props: { rows, now } })

    expect(list.findAll('.list__due').map((item) => item.text())).toEqual([
      'Повторить сегодня',
      'Завтра',
      'Через 21 день',
      'Выучено · через 30 дней',
    ])
  })

  it('фильтр «сегодня» и по модулю, поиск по слову и переводу', async () => {
    const list = mount(WordList, { props: { rows, now } })
    const filters = list.findAll('.list__filter')

    expect(filters.map((item) => item.text())).toEqual([
      'Все · 4',
      'Сегодня · 1',
      'Модуль 1',
      'Модуль 2',
    ])
    await filters[1]!.trigger('click')
    expect(words(list)).toEqual(['wear'])
    expect(filters[1]!.attributes('aria-pressed')).toBe('true')

    await filters[3]!.trigger('click')
    expect(words(list)).toEqual(['cry', 'own'])

    await filters[0]!.trigger('click')
    await list.get('input').setValue('LOOK-пер')
    expect(words(list)).toEqual(['look'])

    await list.get('input').setValue('нет такого')
    expect(list.text()).toContain('Ничего не нашлось')
  })
})

describe('ReviewSession', () => {
  const label = () => 'Модуль 1 · Урок 1'

  it('слово → перевод → «Помню» / «Не помню» → итог', async () => {
    const review = mount(ReviewSession, { props: { words: courseWords, lessonLabel: label } })

    expect(review.get('h2').text()).toBe('wear')
    expect(review.text()).not.toContain('носить (одежду)')
    await review.get('button').trigger('click')
    expect(review.text()).toContain('носить (одежду)')
    expect(review.text()).toContain('She is wearing a red jacket.')

    await review.get('.review__forgot').trigger('click')
    expect(review.get('h2').text()).toBe('at the moment')
    await review.get('button').trigger('click')
    await review.findAll('button').slice(-1)[0]!.trigger('click')

    expect(review.emitted('answer')).toEqual([
      ['wear', false],
      ['at-the-moment', true],
    ])
    expect(review.text()).toContain('Повторение закончено')
    expect(review.findAll('.review__stat strong').map((item) => item.text())).toEqual(['1', '1'])
  })

  it('фокус — на следующем действии: «Помню» после перевода, «Показать перевод» после ответа', async () => {
    const review = mount(ReviewSession, {
      props: { words: courseWords, lessonLabel: label },
      attachTo: document.body,
    })

    await review.get('button').trigger('click')
    await nextTick()
    expect(document.activeElement?.textContent?.trim()).toBe('Помню')
    // Перевод — в живой области, которая была в странице заранее
    expect(review.get('[aria-live="polite"]').text()).toContain('носить (одежду)')

    await review.findAll('button').slice(-1)[0]!.trigger('click')
    await nextTick()
    expect(document.activeElement?.textContent?.trim()).toBe('Показать перевод')
    review.unmount()
  })

  it('«Пройти ещё раз» — для себя: ответы в расписание не уходят', async () => {
    const review = mount(ReviewSession, {
      props: { words: [courseWords[0]!], lessonLabel: label },
      slots: {
        done: `<template #done="{ restart }"><button class="again" @click="restart">ещё</button></template>`,
      },
    })
    await review.get('button').trigger('click')
    await review.findAll('button').slice(-1)[0]!.trigger('click')
    await review.get('.again').trigger('click')

    expect(review.text()).toContain('расписание повторения не меняется')
    await review.get('button').trigger('click')
    await review.findAll('button').slice(-1)[0]!.trigger('click')
    expect(review.emitted('answer')).toHaveLength(1)
  })
})
