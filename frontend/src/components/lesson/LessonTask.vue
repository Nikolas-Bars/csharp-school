<script setup lang="ts">
import { computed, defineAsyncComponent, onBeforeUnmount, ref, useTemplateRef, watch } from 'vue'

import CodeBlock from '@/components/lesson/CodeBlock.vue'
import MarkdownView from '@/components/lesson/MarkdownView.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import { useDraft } from '@/composables/useDraft'
import { useRunner } from '@/composables/useRunner'
import { t } from '@/i18n'
import {
  describeCheck,
  explainDiagnostic,
  explainRuntimeError,
  explainSetError,
  visibleDiagnostics,
} from '@/runner/explain'
import type { Diagnostic, TestResponse } from '@/runner/protocol'
import type { TaskStep } from '@/types/api'

/**
 * Задача урока по макету: слева условие и подсказки по шагам, справа редактор и проверки.
 * На телефоне — вкладки «Условие / Код / Проверки». Код проверяет runner в браузере.
 */
/** solution — последний зачтённый код этой задачи (из прогресса), если черновика нет */
const { task, draftKey, solution } = defineProps<{
  task: TaskStep
  draftKey: string
  solution?: string
}>()
const emit = defineEmits<{ passed: [code: string]; next: [] }>()

// Редактор тяжёлый (CodeMirror) — грузится только на шаге с задачей
const CodeEditor = defineAsyncComponent(() => import('@/components/lesson/CodeEditor.vue'))
const editor = useTemplateRef<{ insert(text: string, cursorOffset?: number): void }>('editor')

const runner = useRunner()
runner.preload()

const code = useDraft(draftKey, solution ?? task.starter)
const hintsShown = ref(0)
const tab = ref<'statement' | 'code' | 'checks'>('statement')
const state = ref<
  { kind: 'idle' | 'running' | 'timeout' | 'crashed' } | { kind: 'done'; response: TestResponse }
>({ kind: 'idle' })
const diagnostics = ref<Diagnostic[]>([])

const response = computed(() => (state.value.kind === 'done' ? state.value.response : null))
const passedCount = computed(
  () => response.value?.results.filter((result) => result.passed).length ?? 0,
)
/** Вывод программы как она есть: из первой проверки вывода без подменённых значений (set) */
const output = computed(() => {
  const outputs = task.checks.flatMap((check, index) =>
    check.kind === 'output' ? [{ check, index }] : [],
  )
  const plain = outputs.find(({ check }) => !check.set || Object.keys(check.set).length === 0)
  const index = (plain ?? outputs[0])?.index
  return index === undefined ? null : (response.value?.results[index]?.actual ?? null)
})

/** Кнопки символов для телефона: пара скобок — курсор между ними */
const KEYS: { label: string; text: string; cursor: number }[] = [
  { label: ';', text: ';', cursor: 1 },
  { label: '( )', text: '()', cursor: 1 },
  { label: '" "', text: '""', cursor: 1 },
  { label: '{ }', text: '{}', cursor: 1 },
  { label: '=', text: ' = ', cursor: 3 },
  { label: '+', text: ' + ', cursor: 3 },
]

async function run() {
  state.value = { kind: 'running' }
  tab.value = 'checks'
  const outcome = await runner.test(code.value, task.checks)

  if (outcome.status !== 'done') {
    state.value = { kind: outcome.status }
    return
  }
  state.value = { kind: 'done', response: outcome.response }
  diagnostics.value = outcome.response.diagnostics
  if (outcome.response.passed) {
    emit('passed', code.value)
  }
}

function reset() {
  code.value = task.starter
  state.value = { kind: 'idle' }
  diagnostics.value = []
}

// Пока ученик печатает — тихая компиляция, ошибки подчёркиваются ещё до запуска
let timer: ReturnType<typeof setTimeout> | undefined
watch(code, (value) => {
  clearTimeout(timer)
  timer = setTimeout(async () => {
    const outcome = await runner.compile(value)
    if (outcome.status === 'done' && value === code.value) {
      diagnostics.value = outcome.response.diagnostics
    }
  }, 600)
})
onBeforeUnmount(() => clearTimeout(timer))

const errors = computed(() =>
  visibleDiagnostics(response.value?.diagnostics ?? []).filter(
    (diagnostic) => diagnostic.severity === 'error',
  ),
)
</script>

<template>
  <div class="task">
    <div class="task__tabs" role="group" :aria-label="t('task.label')">
      <button
        v-for="name in ['statement', 'code', 'checks'] as const"
        :key="name"
        type="button"
        class="task__tab"
        :aria-pressed="tab === name"
        @click="tab = name"
      >
        {{ t(`task.tabs.${name}`) }}
      </button>
    </div>

    <section class="task__statement" :class="{ 'is-active': tab === 'statement' }">
      <span class="task__label">{{ t('task.label') }}</span>
      <h2 class="task__title">{{ task.title }}</h2>
      <MarkdownView :source="task.statement" />

      <div class="task__hints">
        <div
          v-for="(hint, index) in task.hints.slice(0, hintsShown)"
          :key="index"
          class="task__hint"
        >
          <strong>{{ t('task.hintTitle', { n: index + 1 }) }}</strong>
          <span>{{ hint }}</span>
        </div>
        <button
          v-if="hintsShown < task.hints.length && !response?.passed"
          type="button"
          class="task__hint-button"
          @click="hintsShown++"
        >
          {{
            hintsShown === 0
              ? t('task.hint')
              : t('task.moreHint', { shown: hintsShown, total: task.hints.length })
          }}
        </button>
      </div>
    </section>

    <section class="task__code" :class="{ 'is-active': tab === 'code' }">
      <div class="task__editor">
        <div class="task__toolbar">
          <span class="task__file">{{ t('task.file') }}</span>
          <div class="task__actions">
            <button type="button" class="task__reset" @click="reset">{{ t('task.reset') }}</button>
            <button
              type="button"
              class="task__run"
              :disabled="state.kind === 'running'"
              :aria-busy="state.kind === 'running'"
              @click="run"
            >
              <BaseIcon name="play" :size="14" />
              {{ state.kind === 'running' ? t('task.running') : t('task.run') }}
            </button>
          </div>
        </div>
        <CodeEditor
          ref="editor"
          v-model="code"
          :label="t('task.editor')"
          :diagnostics="diagnostics"
        />
      </div>
      <div class="task__keys" role="group" :aria-label="t('task.keys')">
        <button
          v-for="key in KEYS"
          :key="key.label"
          type="button"
          class="task__key"
          @click="editor?.insert(key.text, key.cursor)"
        >
          {{ key.label }}
        </button>
      </div>
      <p class="task__help">{{ t('task.editorHelp') }}</p>
    </section>

    <section
      class="task__results"
      :class="{ 'is-active': tab === 'checks' }"
      aria-live="polite"
      :aria-busy="state.kind === 'running'"
    >
      <strong class="task__results-title">{{ t('task.checks') }}</strong>

      <p v-if="state.kind === 'idle'" class="task__muted">
        {{ t('task.idle', task.checks.length) }}
      </p>
      <p v-else-if="state.kind === 'running'" class="task__muted">{{ t('runner.loading') }}</p>
      <p v-else-if="state.kind === 'timeout'" class="task__problem">{{ t('runner.timeout') }}</p>
      <p v-else-if="state.kind === 'crashed'" class="task__problem">{{ t('runner.crashed') }}</p>

      <template v-else-if="response && !response.compiled">
        <p class="task__fail">✗ {{ t('task.compileErrors') }}</p>
        <ul class="task__list">
          <li
            v-for="error in errors"
            :key="`${error.line}:${error.column}:${error.code}`"
            class="task__problem"
          >
            <strong>{{ t('runner.line', { line: error.line }) }}:</strong>
            {{ explainDiagnostic(error, response.diagnostics) }}
            <span class="task__original">{{ error.code }}: {{ error.message }}</span>
          </li>
        </ul>
      </template>

      <template v-else-if="response">
        <strong :class="response.passed ? 'task__pass' : 'task__fail'">
          {{
            response.passed
              ? t('task.allPassed')
              : t('task.passed', { passed: passedCount, total: response.results.length })
          }}
        </strong>
        <ul class="task__list">
          <li class="task__pass">✓ {{ t('task.compiles') }}</li>
          <li v-for="result in response.results" :key="result.index">
            <span v-if="result.passed" class="task__pass"
              >✓ {{ describeCheck(task.checks[result.index]!) }}</span
            >
            <div v-else class="task__problem">
              <strong>✗ {{ describeCheck(task.checks[result.index]!) }}</strong>
              <span>{{ task.messages[result.index] }}</span>
              <span v-if="result.setError">{{ explainSetError(result.setError) }}</span>
              <span v-if="result.outputLimitExceeded">{{ t('runner.outputLimit') }}</span>
              <span v-if="result.error">
                {{ t('task.crash')
                }}<template v-if="result.error.line">
                  ({{ t('runner.line', { line: result.error.line }) }})</template
                >: {{ explainRuntimeError(result.error) }}
              </span>
            </div>
          </li>
        </ul>
        <template v-if="output !== null">
          <span class="task__muted">{{ t('task.output') }}</span>
          <CodeBlock :code="output || t('task.noOutput')" language="text" />
        </template>
        <BaseButton v-if="response.passed" class="task__next" @click="emit('next')">
          {{ t('task.next') }}
        </BaseButton>
      </template>
    </section>
  </div>
</template>

<style scoped>
.task {
  display: grid;
  grid-template-columns: minmax(0, 28rem) minmax(0, 1fr);
  grid-template-areas:
    'statement code'
    'statement results';
  gap: var(--space-5);
  align-items: start;
}

.task__tabs {
  display: none;
}

.task__statement {
  grid-area: statement;
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.task__code {
  grid-area: code;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.task__results {
  grid-area: results;
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  min-height: 10rem;
}

.task__label {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--color-accent-text);
  text-transform: uppercase;
}

.task__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xl);
}

.task__hints {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.task__hint {
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-primary-soft);
  color: var(--color-text);
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.task__hint-button {
  align-self: flex-start;
  min-height: 2.75rem;
  padding: var(--space-2) var(--space-4);
  border: 1px solid var(--color-primary);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-accent-text);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.task__editor {
  display: flex;
  flex-direction: column;
  min-height: 20rem;
  border-radius: var(--radius-lg);
  background: var(--color-code-bg);
  overflow: hidden;
}

.task__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-3);
  border-bottom: 1px solid var(--color-code-border);
}

.task__file {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  background: var(--color-code-panel);
  color: var(--color-code-text);
  font-family: var(--font-mono);
  font-size: var(--font-size-sm);
}

.task__actions {
  display: flex;
  gap: var(--space-2);
}

.task__reset,
.task__run {
  min-height: 2.75rem;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-sm);
  font: inherit;
  font-size: var(--font-size-sm);
  cursor: pointer;
}

.task__reset {
  border: 1px solid var(--color-code-border);
  background: transparent;
  color: var(--color-code-text);
}

.task__run {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  border: none;
  background: var(--color-primary);
  color: var(--color-on-accent);
  font-weight: 600;
}

.task__run:disabled {
  opacity: 0.7;
  cursor: progress;
}

.task__reset:focus-visible,
.task__run:focus-visible,
.task__key:focus-visible,
.task__tab:focus-visible,
.task__hint-button:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.task__keys {
  display: none;
}

.task__key {
  min-height: 2.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-surface);
  color: var(--color-text);
  font-family: var(--font-mono);
  font-size: var(--font-size-md);
}

.task__help,
.task__muted,
.task__original {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}

.task__results-title {
  font-size: var(--font-size-lg);
}

.task__list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.task__pass {
  color: var(--color-success-text);
}

.task__fail {
  margin: 0;
  color: var(--color-danger-text);
}

.task__problem {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-danger-soft);
  color: var(--color-danger-text);
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.task__next {
  align-self: flex-start;
}

/* Телефон и узкое окно: вкладки вместо двух колонок, кнопки символов под редактором */
@media (max-width: 56rem) {
  .task {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas: 'tabs' 'statement' 'code' 'results';
  }

  .task__tabs {
    grid-area: tabs;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--space-1);
    padding: var(--space-1);
    border-radius: var(--radius);
    background: var(--color-surface-muted);
  }

  .task__tab {
    min-height: 2.75rem;
    border: none;
    border-radius: var(--radius-sm);
    background: transparent;
    color: var(--color-text-muted);
    font: inherit;
    font-weight: 600;
  }

  .task__tab[aria-pressed='true'] {
    background: var(--color-surface);
    color: var(--color-text);
  }

  .task__statement,
  .task__code,
  .task__results {
    display: none;
  }

  .task__statement.is-active,
  .task__code.is-active,
  .task__results.is-active {
    display: flex;
  }

  .task__keys {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: var(--space-1);
  }

  .task__help {
    display: none;
  }
}
</style>
