<script setup lang="ts">
import { useId } from 'vue'

import { t } from '@/i18n'
import type { ChoiceExercise } from '@/types/api'

import type { Tone } from './tone'

/** Выбрать вариант: настоящие радиокнопки, буквы A, B, C — для глаз */
const { options, tone, locked, shown } = defineProps<{
  options: ChoiceExercise['options']
  tone: Tone
  locked: boolean
  /** Верный вариант после второй неудачи — подсвечен, его нужно выбрать */
  shown: number | null
}>()
const selected = defineModel<number | null>({ required: true })

const name = useId()
const letter = (index: number) => String.fromCharCode(65 + index)
</script>

<template>
  <fieldset class="choice" :disabled="locked">
    <legend class="visually-hidden">{{ t('exercise.options') }}</legend>
    <label
      v-for="(option, index) in options"
      :key="index"
      class="choice__option"
      :class="{
        'choice__option--selected': selected === index,
        [`choice__option--${tone}`]: selected === index && tone !== 'idle',
        'choice__option--shown': shown === index && selected !== index,
      }"
    >
      <input v-model="selected" class="visually-hidden" type="radio" :name="name" :value="index" />
      <span class="choice__letter" aria-hidden="true">{{ letter(index) }}</span>
      <span lang="en">{{ option.text }}</span>
    </label>
  </fieldset>
</template>

<style scoped>
.choice {
  margin: 0;
  padding: 0;
  border: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.choice__option {
  min-height: 3.5rem;
  padding: var(--space-3) var(--space-4);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  align-items: center;
  gap: var(--space-4);
  font-size: var(--font-size-lg);
  cursor: pointer;
}

.choice__option:hover {
  background: var(--color-surface-muted);
}

.choice__option:has(input:focus-visible) {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.choice__letter {
  flex: none;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-sm);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-sm);
  font-weight: 700;
}

.choice__option--selected {
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
}

.choice__option--selected .choice__letter {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.choice__option--right {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.choice__option--right .choice__letter {
  background: var(--color-success);
}

.choice__option--wrong {
  border-color: var(--color-danger);
  background: var(--color-danger-soft);
}

.choice__option--wrong .choice__letter {
  background: var(--color-danger);
}

.choice__option--shown {
  border-color: var(--color-success);
}

.choice__option--shown .choice__letter {
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.choice:disabled .choice__option {
  cursor: default;
}
</style>
