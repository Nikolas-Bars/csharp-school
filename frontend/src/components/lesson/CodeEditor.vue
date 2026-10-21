<script setup lang="ts">
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands'
import {
  bracketMatching,
  HighlightStyle,
  indentOnInput,
  StreamLanguage,
  syntaxHighlighting,
} from '@codemirror/language'
import { setDiagnostics, type Diagnostic as EditorDiagnostic } from '@codemirror/lint'
import { EditorState, type Text } from '@codemirror/state'
import {
  drawSelection,
  EditorView,
  highlightActiveLine,
  keymap,
  lineNumbers,
} from '@codemirror/view'
import { tags } from '@lezer/highlight'
import { onBeforeUnmount, onMounted, useTemplateRef, watch } from 'vue'

import { explainDiagnostic, visibleDiagnostics } from '@/runner/explain'
import type { Diagnostic } from '@/runner/protocol'
import { PATTERNS, wordKind, type TokenKind } from '@/utils/csharpTokens'

/**
 * Редактор кода (CodeMirror 6), тёмный в любой теме. Раскраска — те же правила, что у блоков
 * кода в теории (utils/csharpTokens). Ошибки компилятора подчёркиваются, в подсказке — понятный
 * текст. Загружается лениво: только на шаге с задачей.
 */
const { label, diagnostics = [] } = defineProps<{ label: string; diagnostics?: Diagnostic[] }>()

const code = defineModel<string>({ required: true })

const host = useTemplateRef<HTMLDivElement>('host')
let view: EditorView | null = null

const STYLE: Record<TokenKind, string | null> = {
  keyword: 'keyword',
  type: 'typeName',
  string: 'string',
  number: 'number',
  comment: 'comment',
  plain: null,
}

const csharp = StreamLanguage.define<{ inComment: boolean }>({
  name: 'csharp',
  startState: () => ({ inComment: false }),
  token(stream, state) {
    if (state.inComment || stream.match('/*')) {
      state.inComment = !stream.skipTo('*/')
      if (state.inComment) {
        stream.skipToEnd()
      } else {
        stream.match('*/')
      }
      return 'comment'
    }
    if (stream.eatSpace()) {
      return null
    }
    for (const [kind, regex] of PATTERNS) {
      if (stream.match(regex)) {
        return STYLE[kind]
      }
    }
    const word = stream.match(/^[A-Za-z_]\w*/)
    if (Array.isArray(word)) {
      return STYLE[wordKind(word[0])]
    }
    stream.next()
    return null
  },
})

const highlight = HighlightStyle.define([
  { tag: tags.keyword, color: 'var(--color-code-keyword)' },
  { tag: tags.typeName, color: 'var(--color-code-type)' },
  { tag: tags.string, color: 'var(--color-code-string)' },
  { tag: tags.number, color: 'var(--color-code-number)' },
  { tag: tags.comment, color: 'var(--color-code-comment)' },
])

const theme = EditorView.theme(
  {
    '&': {
      color: 'var(--color-code-text)',
      backgroundColor: 'var(--color-code-bg)',
      height: '100%',
    },
    '&.cm-focused': { outline: 'none' },
    '.cm-scroller': {
      fontFamily: 'var(--font-mono)',
      fontSize: 'var(--font-size-md)',
      lineHeight: '1.75',
    },
    '.cm-content': { caretColor: 'var(--color-code-text)', padding: 'var(--space-3) 0' },
    '.cm-cursor': { borderLeftColor: 'var(--color-code-text)' },
    '.cm-gutters': {
      backgroundColor: 'var(--color-code-bg)',
      color: 'var(--color-code-gutter)',
      border: 'none',
    },
    '.cm-activeLine': { backgroundColor: 'var(--color-code-panel)' },
    '&.cm-focused .cm-selectionBackground, .cm-selectionBackground': {
      backgroundColor: 'var(--color-code-border)',
    },
    '.cm-tooltip': {
      backgroundColor: 'var(--color-surface)',
      color: 'var(--color-text)',
      border: '1px solid var(--color-border)',
    },
    '.cm-diagnostic': { fontFamily: 'var(--font-sans)', maxWidth: '28rem' },
  },
  { dark: true },
)

/** Позиция с 1 (как в протоколе runner) → смещение в документе CodeMirror */
function offset(doc: Text, line: number, column: number): number {
  const target = doc.line(Math.min(Math.max(line, 1), doc.lines))
  return Math.min(target.from + Math.max(column - 1, 0), target.to)
}

function toEditor(doc: Text, list: Diagnostic[]): EditorDiagnostic[] {
  return visibleDiagnostics(list).map((diagnostic) => {
    let from = offset(doc, diagnostic.line, diagnostic.column)
    let to = Math.max(offset(doc, diagnostic.endLine, diagnostic.endColumn), from)
    // Ошибка нулевой длины («не хватает ;» после «)») — подчёркиваем символ перед ней, а в начале
    // строки — после: иначе видна только точка, которую новичок не заметит
    if (to === from) {
      const line = doc.lineAt(from)
      if (from > line.from) {
        from -= 1
      } else {
        to = Math.min(from + 1, line.to)
      }
    }
    return {
      from,
      to,
      severity: diagnostic.severity,
      message: `${explainDiagnostic(diagnostic, list)}\n${diagnostic.code}: ${diagnostic.message}`,
    }
  })
}

onMounted(() => {
  view = new EditorView({
    parent: host.value ?? undefined,
    state: EditorState.create({
      doc: code.value,
      extensions: [
        lineNumbers(),
        history(),
        drawSelection(),
        highlightActiveLine(),
        indentOnInput(),
        bracketMatching(),
        csharp,
        syntaxHighlighting(highlight),
        keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
        theme,
        EditorView.contentAttributes.of({
          'aria-label': label,
          spellcheck: 'false',
          autocapitalize: 'off',
        }),
        EditorView.updateListener.of((update) => {
          if (update.docChanged) {
            code.value = update.state.doc.toString()
          }
        }),
      ],
    }),
  })
  view.dispatch(setDiagnostics(view.state, toEditor(view.state.doc, diagnostics)))
})

// Код поменяли снаружи («Сбросить») — изменение идёт транзакцией: Ctrl+Z его отменит
watch(code, (value) => {
  if (view && value !== view.state.doc.toString()) {
    view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: value } })
  }
})

watch(
  () => diagnostics,
  (list) => view?.dispatch(setDiagnostics(view.state, toEditor(view.state.doc, list))),
)

onBeforeUnmount(() => view?.destroy())

/** Вставить текст у курсора (кнопки символов на телефоне); пара скобок — курсор между ними */
function insert(text: string, cursorOffset = text.length) {
  if (!view) {
    return
  }
  const { from, to } = view.state.selection.main
  view.dispatch({ changes: { from, to, insert: text }, selection: { anchor: from + cursorOffset } })
  view.focus()
}

defineExpose({ insert })
</script>

<template>
  <div ref="host" class="editor"></div>
</template>

<style scoped>
.editor {
  height: 100%;
  min-height: 16rem;
}
</style>
