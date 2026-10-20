import { t } from '@/i18n'
import ru from '@/i18n/locales/ru.json'

import type { Diagnostic, RuntimeError } from './protocol'

type Group = 'compiler' | 'runtime'
type Known<G extends Group> = Exclude<keyof (typeof ru)[G], 'unknown'>

/** Есть ли перевод для этого кода ошибки (языки сверяет i18n.spec.ts, поэтому достаточно русского) */
function isKnown<G extends Group>(group: G, code: string): code is Known<G> & string {
  return code !== 'unknown' && code in ru[group]
}

/**
 * Ошибка компилятора простыми словами: перевод по коду (CS0103…) с подстановкой аргументов
 * как {arg0}, {arg1}. Для кода без перевода — общий текст; оригинал показывается рядом всегда.
 */
export function explainDiagnostic(diagnostic: Diagnostic): string {
  if (!isKnown('compiler', diagnostic.code)) {
    return t('compiler.unknown')
  }

  const values = Object.fromEntries(
    diagnostic.arguments.map((value, index) => [`arg${index}`, value]),
  )
  return t(`compiler.${diagnostic.code}`, values)
}

/** Исключение, на котором упала программа, простыми словами */
export function explainRuntimeError(error: RuntimeError): string {
  return isKnown('runtime', error.type)
    ? t(`runtime.${error.type}`)
    : t('runtime.unknown', { type: error.type })
}
