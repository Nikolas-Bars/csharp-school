/**
 * Разбор Markdown учебного контента в структуру — без HTML: страница строит элементы сама,
 * поэтому в тексте урока не может оказаться чужой разметки или скрипта.
 *
 * Только то, что пишется в content/: заголовки #–###, абзацы, списки «- » и «1. », **жирный**,
 * `код`, ==слово-маркер==, блоки кода ```язык, таблицы «| a | b |», заметка «> текст». Для
 * английского — блоки ```example (пример со звуком: строка по-английски, под ней перевод; примеры
 * через пустую строку) и ```mistake (строка с ошибкой, верная строка, объяснение; тоже через
 * пустую строку). Остальное выводится как обычный текст.
 */
export interface Inline {
  kind: 'text' | 'strong' | 'code' | 'mark'
  text: string
}

/** Пример: английская фраза (её можно послушать) и перевод */
export interface Example {
  en: string
  translation: string
}

/** Частая ошибка: как неверно, как верно и почему */
export interface MistakeNote {
  wrong: string
  right: string
  why: string
}

export type Block =
  | { kind: 'heading'; level: 1 | 2 | 3; content: Inline[] }
  | { kind: 'paragraph'; content: Inline[] }
  | { kind: 'list'; ordered: boolean; items: Inline[][] }
  | { kind: 'code'; language: string; code: string }
  | { kind: 'table'; head: Inline[][]; rows: Inline[][][] }
  | { kind: 'note'; content: Inline[] }
  | { kind: 'examples'; items: Example[] }
  | { kind: 'mistakes'; items: MistakeNote[] }

const FENCE = /^```(\w*)\s*$/
const HEADING = /^(#{1,3})\s+(.*)$/
const LIST_ITEM = /^[-*]\s+(.*)$/
const ORDERED_ITEM = /^\d+\.\s+(.*)$/
const TABLE_ROW = /^\|.*\|\s*$/
const TABLE_SEPARATOR = /^\|(\s*:?-+:?\s*\|)+\s*$/
const NOTE = /^>\s+(.*)$/

/** Начало другого блока — абзац на нём заканчивается */
const BLOCK_START = [FENCE, HEADING, LIST_ITEM, ORDERED_ITEM, TABLE_ROW, NOTE]

export function parseMarkdown(source: string): Block[] {
  const lines = source.replace(/\r\n?/g, '\n').split('\n')
  const blocks: Block[] = []
  let index = 0

  while (index < lines.length) {
    const line = lines[index] ?? ''
    const fence = FENCE.exec(line)
    const heading = HEADING.exec(line)

    if (line.trim() === '') {
      index++
    } else if (fence) {
      const code: string[] = []
      index++
      while (index < lines.length && !FENCE.test(lines[index] ?? '')) {
        code.push(lines[index++] ?? '')
      }
      index++ // закрывающие ```
      blocks.push(fenced(fence[1] ?? '', code))
    } else if (heading) {
      const level = (heading[1] ?? '#').length as 1 | 2 | 3
      blocks.push({ kind: 'heading', level, content: parseInline(heading[2] ?? '') })
      index++
    } else if (LIST_ITEM.test(line) || ORDERED_ITEM.test(line)) {
      // Пункты одного вида подряд: «- » — обычный список, «1. » — нумерованный
      const ordered = ORDERED_ITEM.test(line)
      const pattern = ordered ? ORDERED_ITEM : LIST_ITEM
      const items: Inline[][] = []
      while (index < lines.length && pattern.test(lines[index] ?? '')) {
        let item = pattern.exec(lines[index++] ?? '')?.[1] ?? ''
        // Продолжение пункта — строка с отступом
        while (/^\s{2,}\S/.test(lines[index] ?? '')) {
          item += ' ' + (lines[index++] ?? '').trim()
        }
        items.push(parseInline(item))
      }
      blocks.push({ kind: 'list', ordered, items })
    } else if (TABLE_ROW.test(line) && TABLE_SEPARATOR.test(lines[index + 1] ?? '')) {
      // Строка заголовков, разделитель «|---|---|», дальше строки таблицы
      const head = cells(line)
      const rows: Inline[][][] = []
      index += 2
      while (index < lines.length && TABLE_ROW.test(lines[index] ?? '')) {
        rows.push(cells(lines[index++] ?? ''))
      }
      blocks.push({ kind: 'table', head, rows })
    } else if (NOTE.test(line)) {
      const text: string[] = []
      while (index < lines.length && NOTE.test(lines[index] ?? '')) {
        text.push(NOTE.exec(lines[index++] ?? '')?.[1] ?? '')
      }
      blocks.push({ kind: 'note', content: parseInline(text.join(' ')) })
    } else {
      const text: string[] = []
      do {
        text.push((lines[index++] ?? '').trim())
      } while (
        index < lines.length &&
        (lines[index] ?? '').trim() !== '' &&
        !BLOCK_START.some((pattern) => pattern.test(lines[index] ?? ''))
      )
      blocks.push({ kind: 'paragraph', content: parseInline(text.join(' ')) })
    }
  }

  return blocks
}

/** Блок кода; ```example и ```mistake — блоки английского урока */
function fenced(language: string, code: string[]): Block {
  if (language === 'example') {
    return {
      kind: 'examples',
      items: groups(code).map(([en = '', ...translation]) => ({
        en,
        translation: translation.join(' '),
      })),
    }
  }
  if (language === 'mistake') {
    return {
      kind: 'mistakes',
      items: groups(code).map(([wrong = '', right = '', ...why]) => ({
        wrong,
        right,
        why: why.join(' '),
      })),
    }
  }
  return { kind: 'code', language, code: code.join('\n') }
}

/** Строки блока группами через пустую строку, без пустых групп */
function groups(lines: string[]): string[][] {
  const result: string[][] = [[]]
  for (const line of lines) {
    if (line.trim() === '') {
      result.push([])
    } else {
      result[result.length - 1]!.push(line.trim())
    }
  }
  return result.filter((group) => group.length > 0)
}

/** Ячейки строки таблицы «| a | **b** |» */
function cells(row: string): Inline[][] {
  return row
    .trim()
    .replace(/^\||\|$/g, '')
    .split('|')
    .map((cell) => parseInline(cell.trim()))
}

/**
 * `код`, **жирный** и ==слово-маркер==; код важнее: звёздочки и знаки равенства внутри кода — это
 * код (`a == b`)
 */
export function parseInline(text: string): Inline[] {
  const parts: Inline[] = []
  const pattern = /`([^`]+)`|\*\*(.+?)\*\*|==(\S(?:.*?\S)?)==/g
  let last = 0

  for (const match of text.matchAll(pattern)) {
    if (match.index > last) {
      parts.push({ kind: 'text', text: text.slice(last, match.index) })
    }
    parts.push(
      match[1] !== undefined
        ? { kind: 'code', text: match[1] }
        : match[2] !== undefined
          ? { kind: 'strong', text: match[2] }
          : { kind: 'mark', text: match[3] ?? '' },
    )
    last = match.index + match[0].length
  }

  if (last < text.length) {
    parts.push({ kind: 'text', text: text.slice(last) })
  }

  return parts
}
