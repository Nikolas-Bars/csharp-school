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
 * тип «void» — вызов метода, который ничего не возвращает; «method group» — имя метода без скобок;
 * CS0029 в bool — почти всегда = вместо == (if (hp = 20)); Length у списка и Count у массива;
 * CS1003 с «,» — чаще всего забытая «;» после объявления (int hp = 5 и перевод строки);
 * CS1656 у переменной foreach и CS0200 у символа строки (name[0] = 'b').
 * Аргументы ошибки подставляются и в эти тексты ({arg0}, {arg1}).
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
      return arg0 === 'void'
        ? 'compilerCases.voidValue'
        : arg1 === 'bool'
          ? 'compilerCases.assignInCondition'
          : null
    case 'CS0815':
      return arg0 === 'void' ? 'compilerCases.voidValue' : null
    case 'CS1003':
      return arg0 === ',' ? 'compilerCases.missingSeparator' : null
    case 'CS1656':
      return arg1 === 'foreach iteration variable' ? 'compilerCases.foreachVariable' : null
    case 'CS0200':
      return arg0 === 'string.this[int]' ? 'compilerCases.stringChar' : null
    case 'CS1061':
      return (arg1 === 'Length' && /^List<.+>$/.test(arg0 ?? '')) ||
        (arg1 === 'Count' && arg0?.endsWith('[]'))
        ? 'compilerCases.countOrLength'
        : null
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
  const values = Object.fromEntries(
    diagnostic.arguments.map((value, index) => [`arg${index}`, value]),
  )

  const special = specialCase(diagnostic, all)
  if (special) {
    return t(special, values)
  }

  if (!isKnown('compiler', diagnostic.code)) {
    return t(diagnostic.severity === 'warning' ? 'compiler.unknownWarning' : 'compiler.unknown')
  }

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

/** Hero.hp → Hero и hp: класс и его член */
function splitMember(member: string): { type: string; name: string } {
  const dot = member.lastIndexOf('.')
  return { type: member.slice(0, Math.max(dot, 0)), name: member.slice(dot + 1) }
}

/** System.Console.WriteLine → Console.WriteLine, Program.Report → Report: ученику это не нужно */
const shortName = (method: string) => method.replace(/^(System|Program)\./, '')

/**
 * Значение из set: 5, 2.5, true, текст — в «ёлочках», массив и список — { 5, 9, 1 }, как в C#.
 * Кавычки C# не угадать: по JSON не видно, string это ("W") или char ('W')
 */
const literal = (value: SetValue): string => {
  if (Array.isArray(value)) {
    return value.length > 0 ? `{ ${value.map((item) => literal(item)).join(', ')} }` : '{ }'
  }
  return typeof value === 'string' ? `«${value}»` : `${value}`
}

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
    case 'access':
      return t('checks.access', splitMember(check.member))
  }
}
