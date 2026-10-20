import { t } from '@/i18n'
import ru from '@/i18n/locales/ru.json'

import type { Check, Diagnostic, RuntimeError } from './protocol'

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

/** Что проверяет проверка задачи — подпись в списке ✓/✗ */
export function describeCheck(check: Check): string {
  switch (check.kind) {
    case 'output':
      return t('checks.output')
    case 'variable':
      return check.type
        ? t('checks.variableTyped', { name: check.name, type: check.type })
        : t('checks.variable', { name: check.name })
    case 'construct':
      return t('checks.construct', { construct: t(`checks.constructs.${check.construct}`) })
    case 'call':
      // System.Console.WriteLine → Console.WriteLine: пространство имён ученику не нужно
      return t('checks.call', { method: check.method.replace(/^System\./, '') })
  }
}
