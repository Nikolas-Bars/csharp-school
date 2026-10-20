import { describe, expect, it } from 'vitest'

import ru from '@/i18n/locales/ru.json'
import { explainDiagnostic, explainRuntimeError } from '@/runner/explain'
import type { Diagnostic } from '@/runner/protocol'

const diagnostic = (code: string, args: string[] = []): Diagnostic => ({
  code,
  severity: 'error',
  message: 'original',
  arguments: args,
  line: 1,
  column: 1,
  endLine: 1,
  endColumn: 2,
})

/**
 * Коды, порядок аргументов которых зафиксирован тестами runner
 * (runner/tests/Runner.Core.Tests/CommonMistakesTests.cs)
 */
const RUNNER_CODES = [
  'CS1002',
  'CS0103',
  'CS0246',
  'CS0029',
  'CS0266',
  'CS0165',
  'CS0128',
  'CS1010',
  'CS1513',
  'CS1026',
  'CS0117',
  'CS1061',
  'CS0019',
  'CS1501',
  'CS0161',
  'CS0201',
  'CS1525',
  'CS0219',
  'CS0168',
  'CS8773',
  'CS8803',
  'CS5001',
  'CS1001',
]

describe('объяснение ошибок', () => {
  it('подставляет аргументы ошибки компилятора', () => {
    expect(explainDiagnostic(diagnostic('CS0103', ['HP']))).toContain('Имя «HP» не найдено')
    expect(explainDiagnostic(diagnostic('CS0029', ['string', 'int']))).toContain(
      'Значение типа string нельзя положить в переменную типа int',
    )
  })

  it('фигурные скобки в тексте не ломают перевод', () => {
    expect(explainDiagnostic(diagnostic('CS1513'))).toBe(
      'Не хватает закрывающей фигурной скобки «}». У каждой «{» должна быть своя пара.',
    )
  })

  it('переведены все частые ошибки из тестов runner, и каждая использует свои аргументы', () => {
    const compiler: Record<string, string> = ru.compiler
    const untranslated = RUNNER_CODES.filter((code) => !compiler[code])
    const unfilled = RUNNER_CODES.filter((code) =>
      /\{arg\d\}/.test(explainDiagnostic(diagnostic(code, ['A', 'B', 'C']))),
    )

    expect(untranslated).toEqual([])
    expect(unfilled).toEqual([])
  })

  it('незнакомый код — общий текст, без выдумок', () => {
    expect(explainDiagnostic(diagnostic('CS9999'))).toBe(ru.compiler.unknown)
    expect(explainDiagnostic(diagnostic('unknown'))).toBe(ru.compiler.unknown)
  })

  it('исключения программы — по типу, незнакомые — с именем типа', () => {
    expect(explainRuntimeError({ type: 'DivideByZeroException', message: '', line: 4 })).toContain(
      'Деление на ноль',
    )
    expect(explainRuntimeError({ type: 'StackOverflowException', message: '', line: null })).toBe(
      'Программа остановилась с ошибкой StackOverflowException.',
    )
  })
})
