import { watch } from 'vue'
import { createI18n, type PluralizationRule } from 'vue-i18n'

import { DEFAULT_LOCALE, locale, type Locale } from './locale'
import en from './locales/en.json'
import ru from './locales/ru.json'

type Messages = typeof ru

/** Все ключи ru.json вида 'home.title': опечатка в ключе — ошибка проверки типов */
type Keys<T, Prefix extends string = ''> = {
  [K in keyof T & string]: T[K] extends string ? `${Prefix}${K}` : Keys<T[K], `${Prefix}${K}.`>
}[keyof T & string]

export type MessageKey = Keys<Messages>

/**
 * Русские формы числа: «1 урок | 3 урока | 5 уроков» (one | few | many).
 * Без этого правила vue-i18n выбирает форму как в английском и пишет «5 урока».
 */
const RUSSIAN_FORMS: Intl.LDMLPluralRule[] = ['one', 'few', 'many']
const russianRules = new Intl.PluralRules('ru')
const russianPlural: PluralizationRule = (count, formsCount) => {
  const form = RUSSIAN_FORMS.indexOf(russianRules.select(Math.abs(count)))
  return Math.min(form === -1 ? RUSSIAN_FORMS.length - 1 : form, formsCount - 1)
}

export const i18n = createI18n<[Messages], Locale, false>({
  legacy: false,
  locale: locale.value,
  fallbackLocale: DEFAULT_LOCALE,
  messages: { ru, en },
  pluralRules: { ru: russianPlural },
})

// Синхронно: текст, собранный сразу после setLocale, уже на новом языке
watch(locale, (value) => (i18n.global.locale.value = value), { flush: 'sync' })

/**
 * Перевод по ключу — в шаблонах и в коде. Число вместо подстановок выбирает форму
 * («5 уроков») и доступно в тексте как {n}.
 */
export function t(key: MessageKey, values: Record<string, unknown> | number = {}): string {
  return typeof values === 'number' ? i18n.global.t(key, values) : i18n.global.t(key, values)
}
