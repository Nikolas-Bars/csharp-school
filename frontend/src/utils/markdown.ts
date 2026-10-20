/**
 * Разбор Markdown учебного контента в структуру — без HTML: страница строит элементы сама,
 * поэтому в тексте урока не может оказаться чужой разметки или скрипта.
 *
 * Только то, что пишется в content/: заголовки #–###, абзацы, списки «- » и «1. », **жирный**,
 * `код`, блоки кода ```язык. Остальное выводится как обычный текст.
 */
export interface Inline {
  kind: 'text' | 'strong' | 'code'
  text: string
}

export type Block =
  | { kind: 'heading'; level: 1 | 2 | 3; content: Inline[] }
  | { kind: 'paragraph'; content: Inline[] }
  | { kind: 'list'; ordered: boolean; items: Inline[][] }
  | { kind: 'code'; language: string; code: string }

const FENCE = /^```(\w*)\s*$/
const HEADING = /^(#{1,3})\s+(.*)$/
const LIST_ITEM = /^[-*]\s+(.*)$/
const ORDERED_ITEM = /^\d+\.\s+(.*)$/

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
      blocks.push({ kind: 'code', language: fence[1] ?? '', code: code.join('\n') })
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
    } else {
      const text: string[] = []
      while (
        index < lines.length &&
        (lines[index] ?? '').trim() !== '' &&
        !FENCE.test(lines[index] ?? '') &&
        !HEADING.test(lines[index] ?? '') &&
        !LIST_ITEM.test(lines[index] ?? '') &&
        !ORDERED_ITEM.test(lines[index] ?? '')
      ) {
        text.push((lines[index++] ?? '').trim())
      }
      blocks.push({ kind: 'paragraph', content: parseInline(text.join(' ')) })
    }
  }

  return blocks
}

/** `код` и **жирный**; код важнее: звёздочки внутри кода — это код */
export function parseInline(text: string): Inline[] {
  const parts: Inline[] = []
  const pattern = /`([^`]+)`|\*\*(.+?)\*\*/g
  let last = 0

  for (const match of text.matchAll(pattern)) {
    if (match.index > last) {
      parts.push({ kind: 'text', text: text.slice(last, match.index) })
    }
    parts.push(
      match[1] !== undefined
        ? { kind: 'code', text: match[1] }
        : { kind: 'strong', text: match[2] ?? '' },
    )
    last = match.index + match[0].length
  }

  if (last < text.length) {
    parts.push({ kind: 'text', text: text.slice(last) })
  }

  return parts
}
