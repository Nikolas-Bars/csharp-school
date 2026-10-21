import { describe, expect, it } from 'vitest'

import ru from '@/i18n/locales/ru.json'
import {
  explainDiagnostic,
  explainRuntimeError,
  explainSetError,
  visibleDiagnostics,
} from '@/runner/explain'
import type { Diagnostic } from '@/runner/protocol'

const diagnostic = (
  code: string,
  args: string[] = [],
  place: Partial<Diagnostic> = {},
): Diagnostic => ({
  code,
  severity: 'error',
  message: 'original',
  arguments: args,
  line: 1,
  column: 1,
  endLine: 1,
  endColumn: 2,
  ...place,
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
  'CS7036',
  'CS1503',
  'CS8421',
  'CS0127',
  'CS0126',
  'CS0106',
  'CS0428',
  'CS0136',
  'CS8321',
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

  it('незнакомый код — общий текст, без выдумок; у предупреждения — свой', () => {
    expect(explainDiagnostic(diagnostic('CS9999'))).toBe(ru.compiler.unknown)
    expect(explainDiagnostic(diagnostic('unknown'))).toBe(ru.compiler.unknown)
    expect(explainDiagnostic({ ...diagnostic('CS9999'), severity: 'warning' })).toBe(
      ru.compiler.unknownWarning,
    )
    expect(explainDiagnostic({ ...diagnostic('unknownWarning'), severity: 'warning' })).toBe(
      ru.compiler.unknownWarning,
    )
  })

  it('методы: аргументы подставляются по порядку', () => {
    expect(explainDiagnostic(diagnostic('CS7036', ['b', 'Damage(int, int)']))).toContain(
      'параметра «b» метода Damage(int, int)',
    )
    expect(explainDiagnostic(diagnostic('CS1503', ['2', 'int', 'string']))).toContain(
      'Аргумент №2: значение типа int не подходит для параметра типа string',
    )
    expect(explainDiagnostic(diagnostic('CS0428', ['Hit', 'int']))).toContain('скобки: Hit().')
  })

  it('void и имя метода без скобок там, где нужно значение, — особый текст', () => {
    const voidValue = ru.compilerCases.voidValue
    expect(explainDiagnostic(diagnostic('CS0029', ['void', 'int']))).toBe(voidValue)
    expect(explainDiagnostic(diagnostic('CS1503', ['1', 'void', 'int']))).toBe(voidValue)
    expect(explainDiagnostic(diagnostic('CS0815', ['void']))).toBe(voidValue)
    expect(explainDiagnostic(diagnostic('CS1503', ['1', 'method group', 'bool']))).toBe(
      ru.compilerCases.methodGroup,
    )
    expect(explainDiagnostic(diagnostic('CS0019', ['+', 'method group', 'int']))).toBe(
      ru.compilerCases.methodGroup,
    )
    // Обычные случаи — обычный текст
    expect(explainDiagnostic(diagnostic('CS0029', ['string', 'int']))).toContain('типа string')
    expect(explainDiagnostic(diagnostic('CS0815', ['null']))).toBe(ru.compiler.unknown)
  })

  it('CS8773: void в $"{…}", другое значение в $"{…}" и просто новая версия C#', () => {
    // Как в CommonMistakesTests: CS1503 с void лежит внутри места CS8773
    const feature = diagnostic('CS8773', ['interpolated string handlers', '10.0'], {
      column: 19,
      endColumn: 38,
    })
    const hole = (type: string) =>
      diagnostic('CS1503', ['1', type, 'object?'], { column: 28, endColumn: 35 })
    const elsewhere = diagnostic('CS1503', ['1', 'void', 'int'], { line: 5, endLine: 5 })

    expect(explainDiagnostic(feature, [hole('void'), feature])).toBe(
      'В фигурных скобках $"…{ }" стоит вызов метода void. Такой метод ничего не возвращает — подставить в текст нечего. Если метод должен отвечать, замени void на тип ответа и верни значение через return.',
    )
    expect(explainDiagnostic(feature, [hole('method group'), feature])).toBe(
      'В фигурных скобках $"…{ }" стоит то, что нельзя подставить в текст. Подставлять можно значения: переменные, числа и вызовы методов, которые возвращают ответ.',
    )
    expect(explainDiagnostic(feature, [elsewhere, feature])).not.toContain('void')
    expect(explainDiagnostic(feature)).not.toContain('void')

    // Ученик видит одно объяснение места, а не два; CS1503 в другом месте остаётся
    expect(visibleDiagnostics([hole('void'), feature, elsewhere])).toEqual([feature, elsewhere])
    expect(visibleDiagnostics([hole('void')])).toEqual([hole('void')])
    expect(explainDiagnostic(diagnostic('CS8773', ['file-scoped namespace', '10.0']))).toBe(
      'Так в C# 9 написать нельзя: это появилось в C# 10.0. Мы учим C# 9 — его понимает Unity. Попробуй записать по-другому.',
    )
  })

  it('тексты обращаются на «ты» без грамматического рода', () => {
    const texts = [
      ...Object.values(ru.compiler),
      ...Object.values(ru.compilerCases),
      ...Object.values(ru.checks.setError),
    ]
    // «Ты забыл(а)…» — так не пишем: обращение к ученику — повелительным наклонением («допиши»)
    const gendered = texts.filter((text) =>
      /(^|[^а-яё])(сделал|написал|забыл|объявил|использовал|вставил|хотел)а?([^а-яё]|$)/iu.test(
        text,
      ),
    )

    expect(gendered).toEqual([])
  })

  it('проверка с другими значениями: почему не получилось подменить', () => {
    expect(explainSetError({ variable: 'height', problem: 'notFound', type: null })).toContain(
      'переменной height в основном коде не нашлось',
    )
    expect(explainSetError({ variable: 'height', problem: 'noValue', type: null })).toContain(
      'Переменная height объявлена без начального значения',
    )
    expect(explainSetError({ variable: 'size', problem: 'notCompiled', type: null })).toContain(
      'с ним код не собирается',
    )
    expect(explainSetError({ variable: 'height', problem: 'wrongType', type: 'string' })).toContain(
      'У переменной height тип string',
    )
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
