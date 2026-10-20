import { vi } from 'vitest'

// Тексты в тестах сверяются с русским, а язык браузера в jsdom — en-US
localStorage.setItem('locale', 'ru')

/**
 * В jsdom нет matchMedia. Замена отвечает на «(prefers-color-scheme: dark)» по
 * device.prefersDark и умеет сообщить подписчикам о смене схемы устройства.
 */
const listeners = new Set<(event: MediaQueryListEvent) => void>()

export const device = {
  prefersDark: false,
  setPrefersDark(value: boolean) {
    device.prefersDark = value
    listeners.forEach((listener) => listener({ matches: value } as MediaQueryListEvent))
  },
  reset() {
    device.prefersDark = false
    listeners.clear()
  },
}

vi.stubGlobal('matchMedia', (query: string) => ({
  get matches() {
    return query.includes('dark') && device.prefersDark
  },
  addEventListener: (_type: string, listener: (event: MediaQueryListEvent) => void) =>
    listeners.add(listener),
}))
