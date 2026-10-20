import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import HomeView from '@/views/HomeView.vue'

describe('HomeView', () => {
  it('показывает заголовок главной', () => {
    const wrapper = mount(HomeView)
    expect(wrapper.find('h1').text()).toBe('Выучи C# по-настоящему. Потом сделай свою игру.')
  })
})
