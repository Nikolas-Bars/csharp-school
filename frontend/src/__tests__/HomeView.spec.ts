import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import HomeView from '@/views/HomeView.vue'

import { testRouter } from './support/router'

describe('HomeView', () => {
  it('заголовок и кнопки: первый урок и программа курса', async () => {
    const wrapper = mount(HomeView, { global: { plugins: [await testRouter()] } })

    expect(wrapper.find('h1').text()).toBe('Выучи C# по-настоящему. Потом сделай свою игру.')
    expect(wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')])).toEqual([
      ['Первый урок — 5 минут', '/courses/csharp/01-first-programs/01-hello'],
      ['Посмотреть программу', '/courses/csharp'],
    ])
  })
})
