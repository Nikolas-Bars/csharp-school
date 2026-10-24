import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import AppHeader from '@/components/layout/AppHeader.vue'
import { useAuthStore } from '@/stores/auth'
import { useCourseStore } from '@/stores/course'

import { apiReply, englishCourse, wordsCourse } from './lessonFixtures'

import { testRouter } from './support/router'

async function mountHeader(pinia = createPinia(), path = '/') {
  return mount(AppHeader, { global: { plugins: [pinia, await testRouter(path)] } })
}

describe('AppHeader', () => {
  it('логотип ведёт на главную и назван для экранного диктора целиком', async () => {
    const link = (await mountHeader()).get('a')

    expect(link.attributes('href')).toBe('/')
    expect(link.attributes('aria-label')).toBe('School — на главную')
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
    expect(guest.findAll('nav a').map((link) => link.text())).toEqual([
      'Курсы',
      'Войти',
      'Создать аккаунт',
    ])

    const pinia = createPinia()
    setActivePinia(pinia)
    useAuthStore().user = { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'ru' }
    const user = await mountHeader(pinia)
    expect(user.text()).toContain('Аккаунт: anya')
    expect(user.findAll('nav a').map((link) => link.text())).toEqual(['Курсы'])
    expect(user.findAll('button').map((button) => button.text())).toContain('Выйти')
  })

  it('«Мой словарь» — только на страницах курса с карточками слов', async () => {
    tokenStorage.clear()
    const links = (header: Awaited<ReturnType<typeof mountHeader>>) =>
      header.findAll('nav a').map((link) => link.text())

    for (const [map, shown] of [
      [wordsCourse, true],
      [englishCourse, false],
    ] as const) {
      // stubGlobal не годится: unstubAllGlobals снял бы и замену matchMedia из setup.ts
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockImplementation(async () => apiReply(map))
      const pinia = createPinia()
      setActivePinia(pinia)
      await useCourseStore().load('english')
      const header = await mountHeader(pinia, '/courses/english')

      expect(links(header).some((text) => text.startsWith('Мой словарь'))).toBe(shown)
      fetchSpy.mockRestore()
    }

    const home = await mountHeader(createPinia(), '/')
    expect(links(home).some((text) => text.startsWith('Мой словарь'))).toBe(false)
  })
})
