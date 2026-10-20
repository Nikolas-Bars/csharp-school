/**
 * Подсветка C#: одни правила для блоков кода в теории (tokenizeCSharp) и для редактора
 * (csharpStreamParser в CodeEditor). Не разбор языка, а раскраска: ключевые слова, типы,
 * строки, числа, комментарии.
 */
export type TokenKind = 'keyword' | 'type' | 'string' | 'number' | 'comment' | 'plain'

export interface Token {
  kind: TokenKind
  text: string
}

export const CSHARP_KEYWORDS = new Set([
  'using',
  'namespace',
  'class',
  'struct',
  'interface',
  'enum',
  'public',
  'private',
  'protected',
  'internal',
  'static',
  'readonly',
  'const',
  'new',
  'return',
  'if',
  'else',
  'switch',
  'case',
  'default',
  'break',
  'continue',
  'for',
  'foreach',
  'in',
  'while',
  'do',
  'true',
  'false',
  'null',
  'this',
  'base',
  'void',
  'var',
  'override',
  'virtual',
  'abstract',
  'sealed',
  'try',
  'catch',
  'finally',
  'throw',
  'is',
  'as',
  'out',
  'ref',
  'params',
  'get',
  'set',
])

export const CSHARP_TYPES = new Set([
  'int',
  'long',
  'short',
  'byte',
  'float',
  'double',
  'decimal',
  'bool',
  'char',
  'string',
  'object',
])

/** Регулярные выражения токенов с начала строки — общие для обоих раскрасчиков */
export const PATTERNS: [TokenKind, RegExp][] = [
  ['comment', /^\/\/.*/],
  ['string', /^\$?@?"(?:[^"\\]|\\.)*"?/],
  ['string', /^'(?:[^'\\]|\\.)*'?/],
  ['number', /^\d+(?:\.\d+)?[fdmFDM]?/],
]

export function wordKind(word: string): TokenKind {
  return CSHARP_TYPES.has(word) ? 'type' : CSHARP_KEYWORDS.has(word) ? 'keyword' : 'plain'
}

export function tokenizeCSharp(code: string): Token[] {
  const tokens: Token[] = []
  let rest = code

  const push = (kind: TokenKind, text: string) => {
    const previous = tokens[tokens.length - 1]
    if (previous && previous.kind === kind) {
      previous.text += text
    } else {
      tokens.push({ kind, text })
    }
  }

  while (rest.length > 0) {
    if (rest.startsWith('/*')) {
      const end = rest.indexOf('*/', 2)
      const text = end === -1 ? rest : rest.slice(0, end + 2)
      push('comment', text)
      rest = rest.slice(text.length)
      continue
    }

    const pattern = PATTERNS.find(([, regex]) => regex.test(rest))
    const word = /^[A-Za-z_]\w*/.exec(rest)?.[0]

    if (pattern) {
      const text = pattern[1].exec(rest)?.[0] ?? rest[0] ?? ''
      push(pattern[0], text)
      rest = rest.slice(text.length)
    } else if (word) {
      push(wordKind(word), word)
      rest = rest.slice(word.length)
    } else {
      push('plain', rest[0] ?? '')
      rest = rest.slice(1)
    }
  }

  return tokens
}
