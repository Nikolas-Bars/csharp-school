import { readonly, ref } from 'vue'

/**
 * Языки интерфейса; первый — по умолчанию. Каждый должен быть и в app.supported_locales бэкенда:
 * с выбранным языком уходят запросы к API.
 */
export const SUPPORTED_LOCALES = ['ru', 'en'] as const

export type Locale = (typeof SUPPORTED_LOCALES)[number]

export const DEFAULT_LOCALE: Locale = SUPPORTED_LOCALES[0]

const STORAGE_KEY = 'locale'

export function isLocale(value: unknown): value is Locale {
  return SUPPORTED_LOCALES.some((code) => code === value)
}

/** Выбранный раньше язык, затем язык браузера («en-US» → «en»), иначе язык по умолчанию */
function detect(): Locale {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (isLocale(saved)) {
    return saved
  }

  const browser = navigator.languages.map((tag) => tag.split('-')[0]?.toLowerCase())
  return browser.find(isLocale) ?? DEFAULT_LOCALE
}

const current = ref<Locale>(detect())
document.documentElement.lang = current.value

/** Текущий язык; менять — только через setLocale */
export const locale = readonly(current)

export function setLocale(value: Locale): void {
  current.value = value
  localStorage.setItem(STORAGE_KEY, value)
  document.documentElement.lang = value
}
