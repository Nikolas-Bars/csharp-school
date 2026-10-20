import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import AppHeader from '@/components/layout/AppHeader.vue'

async function mountHeader() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/', component: {} }],
  })
  await router.push('/')
  return mount(AppHeader, { global: { plugins: [createPinia(), router] } })
}

describe('AppHeader', () => {
  it('логотип ведёт на главную и назван для экранного диктора целиком', async () => {
    const link = (await mountHeader()).get('a')

    expect(link.attributes('href')).toBe('/')
    expect(link.attributes('aria-label')).toBe('C# School — на главную')
  })

  it('кнопка темы говорит, что включено и что будет по нажатию', async () => {
    localStorage.removeItem('theme')
    const button = (await mountHeader()).get('button')

    expect(button.attributes('aria-label')).toBe(
      'Тема: как в системе. Нажми, чтобы включить: светлая',
    )
    await button.trigger('click')
    expect(button.attributes('aria-label')).toBe('Тема: светлая. Нажми, чтобы включить: тёмная')
  })
})
