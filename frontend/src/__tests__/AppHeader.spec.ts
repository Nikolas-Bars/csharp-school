import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import { tokenStorage } from '@/api/tokenStorage'
import AppHeader from '@/components/layout/AppHeader.vue'
import { useAuthStore } from '@/stores/auth'

async function mountHeader(pinia = createPinia()) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: {} },
      { path: '/login', name: 'login', component: {} },
      { path: '/register', name: 'register', component: {} },
    ],
  })
  await router.push('/')
  return mount(AppHeader, { global: { plugins: [pinia, router] } })
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

  it('гостю — «Войти» и «Создать аккаунт», вошедшему — ник и «Выйти»', async () => {
    tokenStorage.clear()
    const guest = await mountHeader()
    expect(guest.findAll('nav a').map((link) => link.text())).toEqual(['Войти', 'Создать аккаунт'])

    const pinia = createPinia()
    setActivePinia(pinia)
    useAuthStore().user = { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'ru' }
    const user = await mountHeader(pinia)
    expect(user.text()).toContain('Ты вошёл как anya')
    expect(user.findAll('nav a')).toHaveLength(0)
    expect(user.findAll('button').map((button) => button.text())).toContain('Выйти')
  })
})
