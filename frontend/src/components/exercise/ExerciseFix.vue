<script setup lang="ts">
import { computed, useId } from 'vue'

import { t } from '@/i18n'
import { words } from '@/utils/answers'

import type { Tone } from './tone'

/** Найти ошибку: выбрать слово (кнопки-переключатели), потом написать, как правильно */
const { text, tone, locked } = defineProps<{ text: string; tone: Tone; locked: boolean }>()
const word = defineModel<number | null>('word', { required: true })
const answer = defineModel<string>('answer', { required: true })

const list = computed(() => words(text))
const id = useId()

function pick(index: number) {
  if (!locked && word.value !== index) {
    word.value = index
    answer.value = ''
  }
}
</script>

<template>
  <div class="fix">
    <p class="fix__lead">{{ t('exercise.fixLead') }}</p>
    <div class="fix__words" role="group" :aria-label="t('exercise.fixWords')">
      <button
        v-for="(item, index) in list"
        :key="index"
        type="button"
        class="fix__word"
        :class="{ 'fix__word--picked': word === index, [`fix__word--${tone}`]: word === index }"
        lang="en"
        :aria-pressed="word === index"
        :disabled="locked"
        @click="pick(index)"
      >
        {{ item }}
      </button>
    </div>
    <div v-if="word !== null" class="fix__answer">
      <label :for="id" class="fix__label">
        {{ t('exercise.fixLabel', { word: list[word] ?? '' }) }}
      </label>
      <input
        :id="id"
        v-model="answer"
        class="fix__input"
        :class="`fix__input--${tone}`"
        lang="en"
        :readonly="locked"
        maxlength="200"
        autocomplete="off"
        autocapitalize="off"
        spellcheck="false"
      />
    </div>
  </div>
</template>

<style scoped>
.fix {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.fix__lead {
  margin: 0;
  font-size: var(--font-size-md);
  color: var(--color-text-muted);
}

.fix__words {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.fix__word {
  min-height: 3rem;
  padding: var(--space-2) var(--space-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
  font-size: var(--font-size-xl);
  cursor: pointer;
}

.fix__word:focus-visible,
.fix__input:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.fix__word--picked {
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
  text-decoration: line-through;
  text-decoration-color: var(--color-danger);
}

.fix__word--picked.fix__word--right {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.fix__word--picked.fix__word--wrong {
  border-color: var(--color-danger);
  background: var(--color-danger-soft);
}

.fix__word:disabled {
  cursor: default;
}

.fix__answer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-3);
}

.fix__label {
  font-weight: 600;
}

.fix__input {
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

.fix__input--right {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.fix__input--wrong {
  border-color: var(--color-danger);
  background: var(--color-danger-soft);
}
</style>
