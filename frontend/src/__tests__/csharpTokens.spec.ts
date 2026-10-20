import { describe, expect, it } from 'vitest'

import { tokenizeCSharp } from '@/utils/csharpTokens'

describe('tokenizeCSharp', () => {
  it('раскрашивает типы, строки, числа и комментарии', () => {
    expect(tokenizeCSharp('int hp = 100; // здоровье\nConsole.WriteLine($"HP: {hp}");')).toEqual([
      { kind: 'type', text: 'int' },
      { kind: 'plain', text: ' hp = ' },
      { kind: 'number', text: '100' },
      { kind: 'plain', text: '; ' },
      { kind: 'comment', text: '// здоровье' },
      { kind: 'plain', text: '\nConsole.WriteLine(' },
      { kind: 'string', text: '$"HP: {hp}"' },
      { kind: 'plain', text: ');' },
    ])
  })

  it('ключевые слова отдельно от похожих имён', () => {
    expect(tokenizeCSharp('if (format)').map((token) => token.kind)).toEqual(['keyword', 'plain'])
  })

  it('кавычки внутри строки и многострочный комментарий', () => {
    expect(tokenizeCSharp('"a\\"b" /* x\ny */')).toEqual([
      { kind: 'string', text: '"a\\"b"' },
      { kind: 'plain', text: ' ' },
      { kind: 'comment', text: '/* x\ny */' },
    ])
  })
})
