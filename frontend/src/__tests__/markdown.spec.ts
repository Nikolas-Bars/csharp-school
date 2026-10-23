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
        ordered: false,
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

  it('нумерованный список отдельно от обычного', () => {
    expect(parseMarkdown('1. Удар героя.\n2. Ответ гоблина.\n- Итог')).toEqual([
      {
        kind: 'list',
        ordered: true,
        items: [
          [{ kind: 'text', text: 'Удар героя.' }],
          [{ kind: 'text', text: 'Ответ гоблина.' }],
        ],
      },
      { kind: 'list', ordered: false, items: [[{ kind: 'text', text: 'Итог' }]] },
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

describe('parseMarkdown: блоки английского урока', () => {
  it('таблица: заголовки, разделитель, строки с **жирным**', () => {
    expect(
      parseMarkdown(
        ['| Кто | Утверждение |', '|---|:---:|', '| I | I **am** working |', '', 'После.'].join(
          '\n',
        ),
      ),
    ).toEqual([
      {
        kind: 'table',
        head: [[{ kind: 'text', text: 'Кто' }], [{ kind: 'text', text: 'Утверждение' }]],
        rows: [
          [
            [{ kind: 'text', text: 'I' }],
            [
              { kind: 'text', text: 'I ' },
              { kind: 'strong', text: 'am' },
              { kind: 'text', text: ' working' },
            ],
          ],
        ],
      },
      { kind: 'paragraph', content: [{ kind: 'text', text: 'После.' }] },
    ])
  })

  it('строка с | без разделителя — обычный текст', () => {
    expect(parseMarkdown('| не таблица |')).toEqual([
      { kind: 'paragraph', content: [{ kind: 'text', text: '| не таблица |' }] },
    ])
  })

  it('примеры: фраза и перевод, через пустую строку', () => {
    expect(
      parseMarkdown(
        [
          '```example',
          'Look! It is raining.',
          'Смотри! Идёт дождь.',
          '',
          "I can't talk right now.",
          'Не могу сейчас говорить:',
          'я за рулём.',
          '```',
        ].join('\n'),
      ),
    ).toEqual([
      {
        kind: 'examples',
        items: [
          { en: 'Look! It is raining.', translation: 'Смотри! Идёт дождь.' },
          { en: "I can't talk right now.", translation: 'Не могу сейчас говорить: я за рулём.' },
        ],
      },
    ])
  })

  it('частые ошибки: неверно, верно, почему', () => {
    expect(
      parseMarkdown(
        [
          '```mistake',
          'I working now.',
          'I am working now.',
          'Без am время не строится.',
          '```',
        ].join('\n'),
      ),
    ).toEqual([
      {
        kind: 'mistakes',
        items: [
          { wrong: 'I working now.', right: 'I am working now.', why: 'Без am время не строится.' },
        ],
      },
    ])
  })

  it('заметка «> » — абзацем, абзац обрывается перед ней', () => {
    expect(parseMarkdown('Текст.\n> **Сравни:** I work\n> и I am working.')).toEqual([
      { kind: 'paragraph', content: [{ kind: 'text', text: 'Текст.' }] },
      {
        kind: 'note',
        content: [
          { kind: 'strong', text: 'Сравни:' },
          { kind: 'text', text: ' I work и I am working.' },
        ],
      },
    ])
  })
})

describe('parseInline: слово-маркер', () => {
  it('==слово== выделяется, `a == b` в коде — код', () => {
    expect(parseInline('Маркеры: ==now== и ==at the moment==, сравни `a == b`.')).toEqual([
      { kind: 'text', text: 'Маркеры: ' },
      { kind: 'mark', text: 'now' },
      { kind: 'text', text: ' и ' },
      { kind: 'mark', text: 'at the moment' },
      { kind: 'text', text: ', сравни ' },
      { kind: 'code', text: 'a == b' },
      { kind: 'text', text: '.' },
    ])
    expect(parseInline('a == b и c == d')).toEqual([{ kind: 'text', text: 'a == b и c == d' }])
  })
})
