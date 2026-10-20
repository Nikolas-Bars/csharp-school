import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'

import { useThemeStore } from '@/stores/theme'

import { device } from './setup'

const htmlTheme = () => document.documentElement.dataset.theme

describe('useThemeStore', () => {
  beforeEach(() => {
    localStorage.removeItem('theme')
    delete document.documentElement.dataset.theme
    device.reset()
    setActivePinia(createPinia())
  })

  it('без выбора следует схеме устройства и меняется вместе с ней', async () => {
    device.prefersDark = true
    const store = useThemeStore()
    expect(store.choice).toBe('system')
    expect(htmlTheme()).toBe('dark')

    device.setPrefersDark(false)
    await nextTick()
    expect(htmlTheme()).toBe('light')
  })

  it('переключается по кругу и возвращается к системной', async () => {
    const store = useThemeStore()
    const seen: string[] = []

    for (let step = 0; step < 3; step++) {
      store.choice = store.next
      await nextTick()
      seen.push(store.choice)
    }

    expect(seen).toEqual(['light', 'dark', 'system'])
  })

  it('запоминает выбранную тему и забывает её при возврате к системной', async () => {
    const store = useThemeStore()

    store.choice = 'dark'
    await nextTick()
    expect(localStorage.getItem('theme')).toBe('dark')
    expect(htmlTheme()).toBe('dark')

    store.choice = 'system'
    await nextTick()
    expect(localStorage.getItem('theme')).toBeNull()
  })

  it('сохранённая тема, которой больше нет, — это системная', () => {
    localStorage.setItem('theme', 'removed')
    expect(useThemeStore().choice).toBe('system')
  })
})
