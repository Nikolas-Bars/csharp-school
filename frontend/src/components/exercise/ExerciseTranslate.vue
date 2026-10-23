<script setup lang="ts">
import { useId } from 'vue'

import { t } from '@/i18n'

import type { Tone } from './tone'

/** Перевести фразу на английский: фраза на языке ученика и поле для перевода */
const { text, exact, tone, locked } = defineProps<{
  text: string
  /** Краткие и полные формы различаются — подсказку «тоже засчитываются» не показываем */
  exact: boolean
  tone: Tone
  locked: boolean
}>()
const answer = defineModel<string>({ required: true })

const id = useId()
</script>

<template>
  <div class="translate">
    <p class="translate__source">«{{ text }}»</p>
    <label :for="id" class="translate__label">{{ t('exercise.translateLabel') }}</label>
    <textarea
      :id="id"
      v-model="answer"
      class="translate__input"
      :class="`translate__input--${tone}`"
      lang="en"
      rows="3"
      :readonly="locked"
      maxlength="200"
      autocomplete="off"
      autocapitalize="sentences"
      spellcheck="false"
    ></textarea>
    <span v-if="!exact" class="translate__forms">{{ t('exercise.translateForms') }}</span>
  </div>
</template>

<style scoped>
.translate {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.translate__source {
  margin: 0;
  font-size: var(--font-size-xl);
  font-weight: 600;
}

.translate__label {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--color-text-muted);
}

.translate__input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border: 2px solid var(--color-primary-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
  font-size: var(--font-size-lg);
  line-height: 1.5;
  resize: vertical;
}

.translate__input:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.translate__input--right {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.translate__input--wrong {
  border-color: var(--color-danger);
  background: var(--color-danger-soft);
}

.translate__forms {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}
</style>
