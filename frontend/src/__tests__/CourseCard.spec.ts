import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import CourseCard from '@/components/course/CourseCard.vue'
import type { CourseSummary } from '@/types/api'

import { csharpSummary, englishSummary } from './lessonFixtures'
import { testRouter } from './support/router'

async function mountCard(course: CourseSummary) {
  return mount(CourseCard, { props: { course }, global: { plugins: [await testRouter()] } })
}

describe('CourseCard', () => {
  it('название, описание и размер курса — с русскими формами числа', async () => {
    const wrapper = await mountCard(csharpSummary)

    expect(wrapper.get('h2').text()).toBe('C# с нуля до первой игры')
    expect(wrapper.text()).toContain('Настоящий компилятор прямо в браузере.')
    expect(wrapper.text()).toContain('5 модулей · 57 уроков')
    expect((await mountCard(englishSummary)).text()).toContain('1 модуль · 1 урок')
  })

  it('пример кода — только у курса, для которого он есть', async () => {
    expect((await mountCard(csharpSummary)).find('pre').text()).toContain('Console.WriteLine')
    expect((await mountCard(englishSummary)).find('pre').exists()).toBe(false)
  })

  it('без первого урока — только программа курса', async () => {
    const wrapper = await mountCard({ ...csharpSummary, start: null })

    expect(wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')])).toEqual([
      ['Программа', '/courses/csharp'],
    ])
  })

  it('карточка названа своим заголовком для экранного диктора', async () => {
    const wrapper = await mountCard(csharpSummary)

    expect(wrapper.get('article').attributes('aria-labelledby')).toBe(
      wrapper.get('h2').attributes('id'),
    )
  })
})
