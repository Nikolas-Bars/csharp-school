<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import BaseButton from '@/components/ui/BaseButton.vue'
import WordCard from '@/components/vocabulary/WordCard.vue'
import { t } from '@/i18n'
import type { WordCard as Card } from '@/types/api'

/**
 * Шаг урока «Новые слова»: карточки по одной с точками. После последней — `done`: слова уходят в
 * словарь, урок идёт дальше.
 */
const { words } = defineProps<{ words: Card[] }>()
const emit = defineEmits<{ done: [] }>()

const index = ref(0)
// Другой урок — с первой карточки
watch(
  () => words,
  () => (index.value = 0),
)

const current = computed(() => words[index.value] ?? null)
const last = computed(() => index.value >= words.length - 1)
const counter = computed(() =>
  t('words.step.counter', { current: index.value + 1, total: words.length }),
)

function next() {
  if (last.value) {
    emit('done')
  } else {
    index.value += 1
  }
}
</script>

<template>
  <section class="steps">
    <div class="steps__top">
      <h2 class="steps__title">{{ t('words.step.title') }}</h2>
      <div class="steps__dots" role="img" :aria-label="counter">
        <span
          v-for="(_, dot) in words"
          :key="dot"
          class="steps__dot"
          :class="{ 'steps__dot--done': dot < index, 'steps__dot--current': dot === index }"
        ></span>
        <span class="steps__counter" aria-hidden="true">{{ counter }}</span>
      </div>
    </div>

    <WordCard v-if="current" :key="current.id" :card="current" />

    <div class="steps__nav">
      <BaseButton variant="ghost" :disabled="index === 0" @click="index -= 1">
        {{ t('words.step.prev') }}
      </BaseButton>
      <BaseButton @click="next">
        {{ last ? t('words.step.done') : t('words.step.next') }}
      </BaseButton>
    </div>
  </section>
</template>

<style scoped>
.steps {
  max-width: 47.5rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.steps__top {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.steps__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xl);
}

.steps__dots {
  display: flex;
  align-items: center;
  gap: var(--space-1);
}

.steps__dot {
  width: 1.5rem;
  height: 0.5rem;
  border-radius: var(--radius-pill);
  background: var(--color-border);
}

.steps__dot--done {
  background: var(--color-success);
}

.steps__dot--current {
  background: var(--color-primary);
}

.steps__counter {
  margin-left: var(--space-2);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

.steps__nav {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: var(--space-3);
}
</style>
