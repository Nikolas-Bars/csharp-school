import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

import { isThemeId, SYSTEM_THEMES, THEMES, type ColorScheme, type ThemeId } from '@/theme/themes'

/** Этот же ключ читает скрипт в index.html, чтобы тема включилась до первой отрисовки */
const STORAGE_KEY = 'theme'

/** 'system' — как на устройстве; меняется вместе с настройкой системы */
export type ThemeChoice = 'system' | ThemeId

/** Порядок переключения кнопкой: системная → светлая → тёмная → системная… */
export const THEME_CHOICES: readonly ThemeChoice[] = ['system', ...THEMES.map((theme) => theme.id)]

const darkQuery = '(prefers-color-scheme: dark)'

function saved(): ThemeChoice {
  const value = localStorage.getItem(STORAGE_KEY)
  return isThemeId(value) ? value : 'system'
}

function deviceScheme(): ColorScheme {
  return window.matchMedia(darkQuery).matches ? 'dark' : 'light'
}

export const useThemeStore = defineStore('theme', () => {
  const choice = ref<ThemeChoice>(saved())
  const scheme = ref<ColorScheme>(deviceScheme())

  window.matchMedia(darkQuery).addEventListener('change', (event) => {
    scheme.value = event.matches ? 'dark' : 'light'
  })

  const active = computed<ThemeId>(() =>
    choice.value === 'system' ? SYSTEM_THEMES[scheme.value] : choice.value,
  )

  const next = computed<ThemeChoice>(() => {
    const index = THEME_CHOICES.indexOf(choice.value)
    return THEME_CHOICES[(index + 1) % THEME_CHOICES.length] ?? 'system'
  })

  watch(choice, (value) => {
    if (value === 'system') {
      localStorage.removeItem(STORAGE_KEY)
    } else {
      localStorage.setItem(STORAGE_KEY, value)
    }
  })

  watch(active, (id) => (document.documentElement.dataset.theme = id), { immediate: true })

  return { choice, active, next }
})
