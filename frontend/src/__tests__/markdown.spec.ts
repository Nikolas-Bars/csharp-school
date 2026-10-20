import { describe, expect, it } from 'vitest'

import { parseInline, parseMarkdown } from '@/utils/markdown'

describe('parseMarkdown', () => {
  it('заголовок, абзац из нескольких строк, список с продолжением и блок кода', () => {
    const blocks = parseMarkdown(
      [
        '# Вывод на экран',
        '',
        'Первая команда —',
        'вывести текст:',
        '',
        '```csharp',
        'Console.WriteLine("Привет");',
        '```',
        '',
        '- `;` — точка с запятой.',
        '- **Console** — это окно,',
        '  куда программа печатает.',
      ].join('\n'),
    )

    expect(blocks).toEqual([
      { kind: 'heading', level: 1, content: [{ kind: 'text', text: 'Вывод на экран' }] },
      { kind: 'paragraph', content: [{ kind: 'text', text: 'Первая команда — вывести текст:' }] },
      { kind: 'code', language: 'csharp', code: 'Console.WriteLine("Привет");' },
      {
        kind: 'list',
        items: [
          [
            { kind: 'code', text: ';' },
            { kind: 'text', text: ' — точка с запятой.' },
          ],
          [
            { kind: 'strong', text: 'Console' },
            { kind: 'text', text: ' — это окно, куда программа печатает.' },
          ],
        ],
      },
    ])
  })

  it('HTML остаётся текстом', () => {
    expect(parseMarkdown('<img src=x onerror=alert(1)>')).toEqual([
      { kind: 'paragraph', content: [{ kind: 'text', text: '<img src=x onerror=alert(1)>' }] },
    ])
  })

  it('звёздочки внутри кода — это код', () => {
    expect(parseInline('`a ** b` и **жирный**')).toEqual([
      { kind: 'code', text: 'a ** b' },
      { kind: 'text', text: ' и ' },
      { kind: 'strong', text: 'жирный' },
    ])
  })

  it('незакрытый блок кода не теряет текст', () => {
    expect(parseMarkdown('```text\nстрока')).toEqual([
      { kind: 'code', language: 'text', code: 'строка' },
    ])
  })
})
