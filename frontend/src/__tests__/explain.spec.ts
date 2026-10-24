import { describe, expect, it } from 'vitest'

import { t } from '@/i18n'
import en from '@/i18n/locales/en.json'
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
  'CS1003',
  'CS0162',
  'CS0642',
  'CS0020',
  'CS0023',
  'CS0841',
  'CS1012',
  'CS0664',
  'CS0200',
  'CS1955',
  'CS1656',
  'CS1526',
  'CS0030',
  'CS0847',
  'CS1579',
  'CS0665',
  'CS1660',
  'CS0122',
  'CS0120',
  'CS0104',
  'CS8801',
  'CS0542',
  'CS0272',
  'CS1729',
  'CS0649',
  'CS1717',
  'CS0176',
  'CS0163',
  'CS8070',
  'CS0131',
  'CS0145',
  'CS0506',
  'CS0115',
  'CS0114',
  'CS0108',
  'CS0621',
  'CS0507',
  'CS0060',
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

  it('= вместо == там, где нужен bool, и Length/Count — особый текст', () => {
    const countOrLength = ru.compilerCases.countOrLength
    // if (hp = 20) и bool canBuy = gold = price — как в CommonMistakesTests
    expect(explainDiagnostic(diagnostic('CS0029', ['int', 'bool']))).toBe(
      'Здесь значение типа int, а нужно true или false. Если ты сравниваешь — пиши два знака ==; один = кладёт значение в переменную.',
    )
    expect(explainDiagnostic(diagnostic('CS0029', ['void', 'bool']))).toBe(
      ru.compilerCases.voidValue,
    )
    expect(explainDiagnostic(diagnostic('CS1061', ['List<int>', 'Length']))).toBe(countOrLength)
    expect(explainDiagnostic(diagnostic('CS1061', ['List<string>', 'Length']))).toBe(countOrLength)
    expect(explainDiagnostic(diagnostic('CS1061', ['int[]', 'Count']))).toBe(countOrLength)
    // Остальные — обычный текст
    expect(explainDiagnostic(diagnostic('CS1061', ['string', 'Lenght']))).toContain(
      'У значения типа string нет «Lenght»',
    )
    expect(explainDiagnostic(diagnostic('CS1061', ['int[]', 'Length']))).toContain('нет «Length»')
    expect(explainDiagnostic(diagnostic('CS1061', ['List<int>', 'Count']))).toContain('нет «Count»')
    expect(explainDiagnostic(diagnostic('CS1061', []))).toContain('У значения типа')
  })

  it('забытая «;» после объявления (CS1003 с «,»), foreach и символ строки — особый текст', () => {
    expect(explainDiagnostic(diagnostic('CS1003', [',']))).toBe(
      'Не хватает знака: скорее всего «;» в конце команды на этой строке или выше, или «,» между значениями.',
    )
    expect(explainDiagnostic(diagnostic('CS1003', ['(']))).toContain('символ «(»')

    expect(explainDiagnostic(diagnostic('CS1656', ['x', 'foreach iteration variable']))).toContain(
      'Переменной «x» нельзя присвоить новое значение: это переменная цикла foreach',
    )
    // Имя метода слева от = — общий текст, без foreach
    const method = explainDiagnostic(diagnostic('CS1656', ['Hit', 'method group']))
    expect(method).toContain('В «Hit» нельзя положить значение')
    expect(method).not.toContain('foreach')

    expect(explainDiagnostic(diagnostic('CS0200', ['string.this[int]']))).toBe(
      ru.compilerCases.stringChar,
    )
    expect(explainDiagnostic(diagnostic('CS0200', ['Array.Length']))).toContain(
      '«Array.Length» можно только читать',
    )
  })

  it('классы и объекты: аргументы подставляются по порядку', () => {
    expect(explainDiagnostic(diagnostic('CS0122', ['Hero.hp']))).toBe(
      'К «Hero.hp» отсюда доступа нет. private (и член класса без слова доступа) виден только внутри своего класса, protected — ещё и в наследниках. Обращайся к нему из методов класса или открой словом public то, что действительно нужно снаружи.',
    )
    expect(explainDiagnostic(diagnostic('CS8801', ['hp']))).toContain('не видно «hp»')
    expect(explainDiagnostic(diagnostic('CS1729', ['Hero', '2']))).toContain(
      'У класса Hero нет конструктора, который принимает столько аргументов (2)',
    )
    expect(explainDiagnostic(diagnostic('CS0649', ['Hero.hp', '0']))).toContain('всегда будет 0')
    expect(explainDiagnostic(diagnostic('CS0176', ['Hero.LevelUp()']))).toContain(
      '«Hero.LevelUp()» помечен static',
    )
    expect(explainDiagnostic({ ...diagnostic('CS1717'), severity: 'warning' })).toContain(
      'this.hp = hp',
    )
  })

  it('перечисления, словари и наследование: аргументы по порядку и особые случаи', () => {
    // Аргументы — как в CommonMistakesTests runner
    expect(explainDiagnostic(diagnostic('CS0506', ['Orc.Attack()', 'Enemy.Attack()']))).toContain(
      'Метод «Enemy.Attack()» в базовом классе не помечен virtual, поэтому «Orc.Attack()» не может',
    )
    expect(
      explainDiagnostic(diagnostic('CS0507', ['Orc.Attack()', 'public', 'Enemy.Attack()'])),
    ).toContain('«Orc.Attack()» переопределяет метод «Enemy.Attack()», у которого доступ public')
    expect(explainDiagnostic(diagnostic('CS0060', ['Orc', 'Enemy']))).toContain(
      'Класс «Orc» открыт словом public, а его базовый класс «Enemy» — нет',
    )
    expect(
      explainDiagnostic({
        ...diagnostic('CS0114', ['Orc.Attack()', 'Enemy.Attack()']),
        severity: 'warning',
      }),
    ).toContain('допиши override')
    expect(explainDiagnostic(diagnostic('CS8803'))).toContain('перечислений (enum)')

    // Пара словаря в foreach, Length у словаря
    expect(explainDiagnostic(diagnostic('CS0200', ['KeyValuePair<string, int>.Value']))).toBe(
      ru.compilerCases.pairReadOnly,
    )
    expect(explainDiagnostic(diagnostic('CS0200', ['KeyValuePair<string, int>.Key']))).toBe(
      ru.compilerCases.pairReadOnly,
    )
    expect(explainDiagnostic(diagnostic('CS1061', ['Dictionary<string, int>', 'Length']))).toBe(
      ru.compilerCases.countOrLength,
    )

    // Число ↔ перечисление; между числами — обычный текст о потере данных
    expect(explainDiagnostic(diagnostic('CS0266', ['int', 'State']))).toContain(
      'в переменную типа State — это перечисление (enum)',
    )
    expect(explainDiagnostic(diagnostic('CS0266', ['State', 'int']))).toContain('(int)state')
    expect(explainDiagnostic(diagnostic('CS0266', ['double', 'int']))).toBe(
      'Значение типа double нельзя просто так положить в int: часть данных может потеряться — например, дробная часть числа.',
    )
    expect(explainDiagnostic(diagnostic('CS0266', ['long', 'int']))).toContain('часть данных')
    expect(explainDiagnostic(diagnostic('CS0266', ['int', 'float']))).toContain('часть данных')
    expect(explainDiagnostic(diagnostic('CS0266', ['int?', 'int']))).toContain('часть данных')
    expect(explainDiagnostic(diagnostic('CS0266', ['object', 'int']))).toContain('часть данных')
    // Базовый класс в переменной наследника
    expect(explainDiagnostic(diagnostic('CS0266', ['Enemy', 'Orc']))).toContain(
      'не каждый Enemy — это Orc',
    )
    // Списки, массивы и интерфейсы — общий текст
    const general = 'Значение типа IEnumerable<int> нельзя просто так положить в List<int>'
    expect(explainDiagnostic(diagnostic('CS0266', ['IEnumerable<int>', 'List<int>']))).toContain(
      general,
    )
    expect(explainDiagnostic(diagnostic('CS0266', ['ITarget', 'Orc']))).toContain('часть данных')
    expect(explainDiagnostic(diagnostic('CS0266', ['Enemy', 'Orc[]']))).toContain('часть данных')

    // Поле, объявленное у наследника ещё раз, и метод с тем же именем — разные тексты
    expect(
      explainDiagnostic({ ...diagnostic('CS0108', ['Orc.hp', 'Enemy.hp']), severity: 'warning' }),
    ).toBe(t('compilerCases.hiddenField', { arg0: 'Orc.hp', arg1: 'Enemy.hp' }))
    expect(
      explainDiagnostic({
        ...diagnostic('CS0108', ['Orc.Attack()', 'Enemy.Attack()']),
        severity: 'warning',
      }),
    ).toContain('пометь метод базового класса словом virtual')

    // CS7036 у конструктора — new без значений или наследник без : base(…); у метода — обычный текст
    const constructor = explainDiagnostic(diagnostic('CS7036', ['name', 'Enemy.Enemy(string)']))
    expect(constructor).toContain('Конструктору Enemy.Enemy(string) не хватает значения для «name»')
    expect(constructor).toContain(': base(…)')
    expect(explainDiagnostic(diagnostic('CS7036', ['hp', 'Hero.Hero(int)']))).toBe(
      t('compilerCases.constructorArgs', { arg0: 'hp', arg1: 'Hero.Hero(int)' }),
    )
    expect(explainDiagnostic(diagnostic('CS7036', ['b', 'Damage(int, int)']))).toContain(
      'метода Damage(int, int)',
    )
    expect(explainDiagnostic(diagnostic('CS7036', ['hp', 'Hero.Heal(int)']))).toContain(
      'метода Hero.Heal(int)',
    )
  })

  it('уже занятое имя: подсказка, как поменять значение без повторного объявления', () => {
    expect(explainDiagnostic(diagnostic('CS0128', ['hp']))).toContain(
      'убери тип перед именем: «hp = 85;»',
    )
  })

  it('аргументы новых ошибок подставляются по порядку', () => {
    expect(explainDiagnostic(diagnostic('CS0664', ['F', 'float']))).toContain(
      'положить его в float, допиши в конце букву: f (или F)',
    )
    expect(explainDiagnostic(diagnostic('CS0023', ['!', 'int']))).toContain(
      'Операцию «!» нельзя применить к значению типа int',
    )
    expect(explainDiagnostic(diagnostic('CS1660', ['lambda expression', 'bool']))).toContain(
      'нужно значение типа bool',
    )
    expect(
      explainDiagnostic(diagnostic('CS0104', ['Random', 'Game.Random', 'System.Random'])),
    ).toContain('Game.Random и System.Random')
    expect(explainDiagnostic(diagnostic('CS0847', ['3']))).toContain('значений должно быть 3')
    expect(explainDiagnostic(diagnostic('CS1526'))).toContain('new int[] { 1, 2 }')
    // Предупреждения без аргументов
    const emptyStatement = explainDiagnostic({ ...diagnostic('CS0642'), severity: 'warning' })
    expect(emptyStatement).toContain('точка с запятой')
    expect(emptyStatement).toContain('while (…); может зациклиться')
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

  it('в текстах нет того, что vue-i18n принимает за HTML (List<int>() и т. п.)', () => {
    // Та же проверка, что у intlify: иначе в консоли предупреждение о HTML в сообщении
    const html = /<\/?[\w\s="/.':;#-/]+>/
    const texts = [ru, en].flatMap((locale) => [
      ...Object.values(locale.compiler),
      ...Object.values(locale.compilerCases),
      ...Object.values(locale.runtime),
    ])

    expect(texts.filter((text) => html.test(text))).toEqual([])
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
    expect(
      explainRuntimeError({ type: 'InvalidOperationException', message: '', line: 3 }),
    ).toContain('элементы списка или словаря, пока он перебирается циклом foreach')
    // Словарь: нет ключа и Add с ключом, который уже есть (типы — из RunTests runner)
    expect(explainRuntimeError({ type: 'KeyNotFoundException', message: '', line: 4 })).toContain(
      'ContainsKey',
    )
    expect(explainRuntimeError({ type: 'ArgumentException', message: '', line: 4 })).toContain(
      'такой ключ в словаре уже есть',
    )
    // Ввода в курсе нет: пример без int.Parse
    expect(explainRuntimeError({ type: 'FormatException', message: '', line: 3 })).not.toContain(
      'Parse',
    )
    expect(explainRuntimeError({ type: 'StackOverflowException', message: '', line: null })).toBe(
      'Программа остановилась с ошибкой StackOverflowException.',
    )
  })
})
