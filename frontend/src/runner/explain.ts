import { t, type MessageKey } from '@/i18n'
import ru from '@/i18n/locales/ru.json'

import type { Check, Diagnostic, RuntimeError, SetError, SetValue } from './protocol'

type Group = 'compiler' | 'runtime'
type General = 'unknown' | 'unknownWarning'
type Known<G extends Group> = Exclude<keyof (typeof ru)[G], General>

/** Есть ли перевод для этого кода ошибки (языки сверяет i18n.spec.ts, поэтому достаточно русского) */
function isKnown<G extends Group>(group: G, code: string): code is Known<G> & string {
  return !(['unknown', 'unknownWarning'] as string[]).includes(code) && code in ru[group]
}

/** Начинается ли inner не раньше и кончается не позже outer (позиции с 1, как в протоколе) */
function inside(inner: Diagnostic, outer: Diagnostic): boolean {
  const before = (a: [number, number], b: [number, number]) =>
    a[0] < b[0] || (a[0] === b[0] && a[1] <= b[1])
  return (
    before([outer.line, outer.column], [inner.line, inner.column]) &&
    before([inner.endLine, inner.endColumn], [outer.endLine, outer.endColumn])
  )
}

/**
 * Частые случаи, где текст по одному коду сбивает с толку. Компилятор узнаёт их по аргументам:
 * тип «void» — вызов метода, который ничего не возвращает; «method group» — имя метода без скобок.
 * В $"…{ }" C# 9 сообщает CS8773 («возможность новой версии»), а что именно не так — CS1503
 * внутри того же места (закреплено в CommonMistakesTests runner)
 */
function specialCase(diagnostic: Diagnostic, all: Diagnostic[]): MessageKey | null {
  const [arg0, arg1, arg2] = diagnostic.arguments

  switch (diagnostic.code) {
    case 'CS8773': {
      if (arg0 !== 'interpolated string handlers') {
        return null
      }
      const voidHole = all.some(
        (other) =>
          other.code === 'CS1503' && other.arguments[1] === 'void' && inside(other, diagnostic),
      )
      return voidHole ? 'compilerCases.voidInInterpolation' : 'compilerCases.interpolationValue'
    }
    case 'CS0029':
    case 'CS0815':
      return arg0 === 'void' ? 'compilerCases.voidValue' : null
    case 'CS1503':
      return arg1 === 'void'
        ? 'compilerCases.voidValue'
        : arg1 === 'method group'
          ? 'compilerCases.methodGroup'
          : null
    case 'CS0019':
      return arg1 === 'method group' || arg2 === 'method group' ? 'compilerCases.methodGroup' : null
    default:
      return null
  }
}

/** CS1503 внутри $"…{ }" — то же место, что CS8773: её смысл уже сказан в тексте CS8773 */
function isInterpolationHole(diagnostic: Diagnostic, all: Diagnostic[]): boolean {
  return (
    diagnostic.code === 'CS1503' &&
    all.some(
      (other) =>
        other.code === 'CS8773' &&
        other.arguments[0] === 'interpolated string handlers' &&
        inside(diagnostic, other),
    )
  )
}

/**
 * Что показать ученику: без повторов одного места двумя объяснениями (void в $"{…}" даёт и
 * CS8773, и CS1503). Объяснять показанное — explainDiagnostic(d, весь список), не этот
 */
export function visibleDiagnostics<T extends Diagnostic>(all: T[]): T[] {
  return all.filter((diagnostic) => !isInterpolationHole(diagnostic, all))
}

/**
 * Ошибка компилятора простыми словами: перевод по коду (CS0103…) с подстановкой аргументов
 * как {arg0}, {arg1}. Для кода без перевода — общий текст (свой для предупреждений); оригинал
 * показывается рядом всегда. all — все сообщения этой компиляции: по соседям уточняются частые случаи
 */
export function explainDiagnostic(diagnostic: Diagnostic, all: Diagnostic[] = []): string {
  const special = specialCase(diagnostic, all)
  if (special) {
    return t(special)
  }

  if (!isKnown('compiler', diagnostic.code)) {
    return t(diagnostic.severity === 'warning' ? 'compiler.unknownWarning' : 'compiler.unknown')
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

/** Почему не получилось проверить программу с другими начальными значениями (set) */
export function explainSetError(error: SetError): string {
  return t(`checks.setError.${error.problem}`, { variable: error.variable, type: error.type ?? '' })
}

/** System.Console.WriteLine → Console.WriteLine, Program.Report → Report: ученику это не нужно */
const shortName = (method: string) => method.replace(/^(System|Program)\./, '')

/**
 * Значение из set: 5, 2.5, true, текст — в «ёлочках». Кавычки C# не угадать: по JSON не видно,
 * string это ("W") или char ('W')
 */
const literal = (value: SetValue) => (typeof value === 'string' ? `«${value}»` : `${value}`)

/** Что проверяет проверка задачи — подпись в списке ✓/✗ */
export function describeCheck(check: Check): string {
  switch (check.kind) {
    case 'output': {
      const values = Object.entries(check.set ?? {}).map(
        ([name, value]) => `${name} = ${literal(value)}`,
      )
      return values.length > 0
        ? t('checks.outputWith', { values: values.join(', ') })
        : t('checks.output')
    }
    case 'variable':
      return check.type
        ? t('checks.variableTyped', { name: check.name, type: check.type })
        : t('checks.variable', { name: check.name })
    case 'construct':
      return t('checks.construct', { construct: t(`checks.constructs.${check.construct}`) })
    case 'call':
      return check.caller
        ? t('checks.callFrom', { method: shortName(check.method), caller: shortName(check.caller) })
        : t('checks.call', { method: shortName(check.method) })
  }
}
