<script setup lang="ts">
import { computed, ref } from 'vue'

import { useRunner } from '@/composables/useRunner'
import { t } from '@/i18n'
import type { RunnerOutcome } from '@/runner/client'
import { explainDiagnostic, explainRuntimeError } from '@/runner/explain'
import type { RunResponse } from '@/runner/protocol'

/** Служебная страница для проверки runner в браузере; маршрут есть только при разработке */

const runner = useRunner()
runner.preload()

const code = ref(`using System;

Console.WriteLine("Как тебя зовут?");
string name = Console.ReadLine();
Console.WriteLine($"Привет, {name}! У героя 100 HP.");`)
const stdin = ref('Аня')
const running = ref(false)
const outcome = ref<RunnerOutcome<RunResponse> | null>(null)
const elapsedMs = ref(0)

const response = computed(() => (outcome.value?.status === 'done' ? outcome.value.response : null))

async function run() {
  running.value = true
  const started = performance.now()
  outcome.value = await runner.run(code.value, stdin.value)
  elapsedMs.value = Math.round(performance.now() - started)
  running.value = false
}
</script>

<template>
  <main class="playground">
    <h1 class="playground__title">{{ t('playground.title') }}</h1>
    <p class="playground__lead">{{ t('playground.lead') }}</p>

    <label class="playground__field">
      <span>{{ t('playground.code') }}</span>
      <textarea v-model="code" class="playground__code" rows="12" spellcheck="false" />
    </label>
    <label class="playground__field">
      <span>{{ t('playground.stdin') }}</span>
      <textarea v-model="stdin" class="playground__code" rows="2" spellcheck="false" />
    </label>

    <div class="playground__actions">
      <button class="playground__run" type="button" :disabled="running" @click="run">
        {{ running ? t('playground.running') : t('playground.run') }}
      </button>
      <span v-if="running" class="playground__muted" role="status">{{ t('runner.loading') }}</span>
      <span v-else-if="outcome" class="playground__muted">{{
        t('playground.time', { ms: elapsedMs })
      }}</span>
    </div>

    <section v-if="outcome" class="playground__result" aria-live="polite">
      <p v-if="outcome.status === 'timeout'" class="playground__problem">
        {{ t('runner.timeout') }}
      </p>
      <p v-else-if="outcome.status === 'crashed'" class="playground__problem">
        {{ t('runner.crashed') }} <span class="playground__muted">{{ outcome.message }}</span>
      </p>

      <template v-if="response">
        <template v-if="response.diagnostics.length > 0">
          <h2 class="playground__heading">{{ t('playground.errors') }}</h2>
          <ul class="playground__diagnostics">
            <li
              v-for="diagnostic in response.diagnostics"
              :key="`${diagnostic.code}:${diagnostic.line}:${diagnostic.column}`"
              :class="['playground__diagnostic', `playground__diagnostic--${diagnostic.severity}`]"
            >
              <strong>{{ t('runner.place', diagnostic) }} · {{ diagnostic.code }}</strong>
              <span>{{ explainDiagnostic(diagnostic) }}</span>
              <span class="playground__muted">{{ t('runner.original', diagnostic) }}</span>
            </li>
          </ul>
        </template>

        <template v-if="response.compiled">
          <h2 class="playground__heading">{{ t('playground.output') }}</h2>
          <pre class="playground__output">{{ response.output || t('playground.noOutput') }}</pre>
          <p v-if="response.outputLimitExceeded" class="playground__problem">
            {{ t('runner.outputLimit') }}
          </p>
          <p v-if="response.error" class="playground__problem">
            <strong
              >{{ t('playground.crash')
              }}<template v-if="response.error.line">
                · {{ t('runner.line', { line: response.error.line }) }}</template
              >:</strong
            >
            {{ explainRuntimeError(response.error) }}
            <span class="playground__muted"
              >{{ response.error.type }}: {{ response.error.message }}</span
            >
          </p>
        </template>
      </template>
    </section>
  </main>
</template>

<style scoped>
.playground {
  max-width: 60rem;
  margin: 0 auto;
  padding: var(--space-5) var(--space-6) var(--space-7);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.playground__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.playground__lead,
.playground__muted {
  margin: 0;
  color: var(--color-text-muted);
}

.playground__muted {
  font-size: var(--font-size-sm);
}

.playground__field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  font-weight: 600;
}

.playground__code,
.playground__output {
  margin: 0;
  padding: var(--space-4);
  border: 1px solid var(--color-code-border);
  border-radius: var(--radius);
  background: var(--color-code-bg);
  color: var(--color-code-text);
  font-family: var(--font-mono);
  font-size: var(--font-size-sm);
  line-height: 1.6;
  white-space: pre-wrap;
}

.playground__code {
  resize: vertical;
}

.playground__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-4);
}

.playground__run {
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-5);
  border: none;
  border-radius: var(--radius);
  background: var(--color-primary);
  color: var(--color-on-accent);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.playground__run:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.playground__run:disabled {
  opacity: 0.7;
  cursor: progress;
}

.playground__result {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.playground__heading {
  margin: 0;
  font-size: var(--font-size-lg);
}

.playground__diagnostics {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.playground__diagnostic {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-sm);
}

.playground__diagnostic--error,
.playground__problem {
  background: var(--color-danger-soft);
  color: var(--color-danger-text);
}

.playground__diagnostic--warning {
  background: var(--color-warning-soft);
  color: var(--color-warning-text);
}

.playground__problem {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-sm);
}
</style>
