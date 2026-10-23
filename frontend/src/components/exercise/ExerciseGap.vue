<script setup lang="ts">
import { computed } from 'vue'

import { t } from '@/i18n'

import type { Tone } from './tone'

/** Вставить слово в пропуск: фраза до ___, поле, фраза после */
const { text, tone, locked } = defineProps<{ text: string; tone: Tone; locked: boolean }>()
const answer = defineModel<string>({ required: true })

const parts = computed(() => {
  const at = text.indexOf('___')
  return at === -1 ? [text, ''] : [text.slice(0, at), text.slice(at + 3)]
})
</script>

<template>
  <p class="gap" lang="en">
    <span>{{ parts[0] }}</span>
    <input
      v-model="answer"
      class="gap__input"
      :class="`gap__input--${tone}`"
      :readonly="locked"
      :aria-label="t('exercise.gapLabel', { text })"
      maxlength="200"
      autocomplete="off"
      autocapitalize="off"
      spellcheck="false"
    />
    <span>{{ parts[1] }}</span>
  </p>
</template>

<style scoped>
.gap {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2) var(--space-3);
  font-size: var(--font-size-xl);
  line-height: 1.6;
}

.gap__input {
  width: 14rem;
  max-width: 100%;
  min-height: 3rem;
  padding: var(--space-2) var(--space-3);
  border: 2px solid var(--color-primary-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
  font-size: var(--font-size-lg);
}

.gap__input:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.gap__input--right {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.gap__input--wrong {
  border-color: var(--color-danger);
  background: var(--color-danger-soft);
}
</style>
