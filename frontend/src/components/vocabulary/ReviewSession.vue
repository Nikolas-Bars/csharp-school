<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'

import BaseButton from '@/components/ui/BaseButton.vue'
import BaseSpeakButton from '@/components/ui/BaseSpeakButton.vue'
import { t } from '@/i18n'
import type { CourseWord } from '@/types/api'

/**
 * Повторение по макету: слово → «Показать перевод» → «Помню» / «Не помню», потом итог. `answer` —
 * ответ на каждое слово (сохраняет вызывающий); «Пройти ещё раз» — те же слова для себя, без ответов
 * в расписание (`practice`).
 */
const { words, lessonLabel } = defineProps<{
  words: CourseWord[]
  /** «Модуль 2 · Урок 1» для слова */
  lessonLabel: (word: CourseWord) => string
}>()
const emit = defineEmits<{ answer: [word: string, remembered: boolean] }>()

const index = ref(0)
const revealed = ref(false)
const answers = ref<boolean[]>([])
/** Второй круг — только для себя: расписание не меняется */
const practice = ref(false)

const current = computed(() => words[index.value] ?? null)

/**
 * Фокус — на следующем действии: кнопки «Показать перевод» и «Помню» сменяют друг друга через v-if, и
 * без этого фокус с клавиатуры улетал бы в начало страницы на каждом слове
 */
const revealButton = ref<{ $el: HTMLElement } | null>(null)
const knewButton = ref<{ $el: HTMLElement } | null>(null)
const doneTitle = ref<HTMLElement | null>(null)

async function reveal() {
  revealed.value = true
  await nextTick()
  knewButton.value?.$el.focus()
}
const finished = computed(() => index.value >= words.length)
const remembered = computed(() => answers.value.filter(Boolean).length)
const example = computed(() => current.value?.examples[0] ?? null)

function answer(known: boolean) {
  if (!current.value) {
    return
  }
  if (!practice.value) {
    emit('answer', current.value.id, known)
  }
  answers.value = [...answers.value, known]
  revealed.value = false
  index.value += 1
  void nextTick(() => (finished.value ? doneTitle.value : revealButton.value?.$el)?.focus())
}

function restart() {
  index.value = 0
  answers.value = []
  revealed.value = false
  practice.value = true
  void nextTick(() => revealButton.value?.$el.focus())
}
</script>

<template>
  <section class="review">
    <div class="review__progress">
      <span class="review__counter">{{
        t('words.review.counter', {
          current: Math.min(index + 1, words.length),
          total: words.length,
        })
      }}</span>
      <div
        class="review__bar"
        role="progressbar"
        :aria-valuenow="Math.min(index, words.length)"
        aria-valuemin="0"
        :aria-valuemax="words.length"
        :aria-label="t('words.review.title')"
      >
        <span
          class="review__fill"
          :style="{ width: `${(Math.min(index, words.length) / words.length) * 100}%` }"
        ></span>
      </div>
    </div>

    <template v-if="current">
      <p v-if="practice" class="review__practice">{{ t('words.review.practice') }}</p>
      <div class="review__card">
        <span class="review__lesson">{{ lessonLabel(current) }}</span>
        <h2 class="review__word" lang="en">{{ current.word }}</h2>
        <span class="review__ipa">{{ current.ipa }}</span>
        <BaseSpeakButton :text="current.word" />
        <!-- Живая область всегда в странице: диктор объявляет перевод, когда он появляется -->
        <div class="review__live" aria-live="polite">
          <div v-if="revealed" class="review__answer">
            <strong class="review__translation">{{ current.translation }}</strong>
            <template v-if="example">
              <span class="review__en" lang="en">{{ example.en }}</span>
              <span class="review__muted">{{ example.translation }}</span>
            </template>
          </div>
        </div>
        <p v-if="!revealed" class="review__muted review__hint">{{ t('words.review.hint') }}</p>
      </div>
      <div class="review__actions">
        <BaseButton v-if="!revealed" ref="revealButton" class="review__wide" @click="reveal">
          {{ t('words.review.reveal') }}
        </BaseButton>
        <template v-else>
          <button type="button" class="review__forgot" @click="answer(false)">
            {{ t('words.review.forgot') }}
          </button>
          <BaseButton ref="knewButton" variant="success" @click="answer(true)">
            {{ t('words.review.knew') }}
          </BaseButton>
        </template>
      </div>
    </template>

    <div v-else-if="finished" class="review__done">
      <h2 ref="doneTitle" class="review__title" tabindex="-1">{{ t('words.review.doneTitle') }}</h2>
      <div class="review__stats">
        <div class="review__stat review__stat--known">
          <strong>{{ remembered }}</strong>
          <span>{{ t('words.review.known') }}</span>
        </div>
        <div class="review__stat review__stat--forgot">
          <strong>{{ answers.length - remembered }}</strong>
          <span>{{
            practice ? t('words.review.forgotPractice') : t('words.review.forgotten')
          }}</span>
        </div>
      </div>
      <ul class="review__results">
        <li v-for="(word, at) in words" :key="word.id" class="review__result">
          <strong lang="en">{{ word.word }}</strong>
          <span :class="answers[at] ? 'review__ok' : 'review__no'">{{
            answers[at] ? t('words.review.resultKnown') : t('words.review.resultForgotten')
          }}</span>
        </li>
      </ul>
      <slot name="done" :restart="restart" />
    </div>
  </section>
</template>

<style scoped>
.review {
  max-width: 35rem;
  width: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.review__progress {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.review__counter {
  align-self: flex-end;
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

.review__bar {
  height: 0.375rem;
  border-radius: var(--radius-pill);
  background: var(--color-border);
  overflow: hidden;
}

.review__fill {
  display: block;
  height: 100%;
  border-radius: var(--radius-pill);
  background: var(--color-primary);
}

.review__practice {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}

.review__card {
  min-height: 18rem;
  padding: var(--space-6) var(--space-5);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  text-align: center;
}

.review__lesson,
.review__muted {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.review__word {
  margin: var(--space-4) 0 0;
  font-family: var(--font-display);
  font-size: var(--font-size-title);
  line-height: 1.15;
  overflow-wrap: anywhere;
}

.review__ipa {
  font-family: var(--font-mono);
  color: var(--color-text-muted);
}

.review__live {
  width: 100%;
}

.review__answer {
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-surface-muted);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.review__translation {
  font-size: var(--font-size-xl);
}

.review__en {
  font-weight: 600;
}

.review__hint {
  margin: auto 0 0;
  line-height: 1.5;
}

.review__actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-3);
}

.review__wide {
  grid-column: 1 / -1;
}

.review__forgot {
  min-height: 2.75rem;
  border-radius: var(--radius);
  border: 1px solid var(--color-danger);
  background: var(--color-surface);
  color: var(--color-danger-text);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.review__forgot:hover {
  background: var(--color-danger-soft);
}

.review__forgot:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.review__done {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.review__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xl);
}

.review__stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-3);
  font-variant-numeric: tabular-nums;
}

.review__stat {
  padding: var(--space-4);
  border-radius: var(--radius);
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.review__stat strong {
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.review__stat--known {
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.review__stat--forgot {
  background: var(--color-danger-soft);
  color: var(--color-danger-text);
}

.review__results {
  margin: 0;
  padding: 0;
  list-style: none;
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.review__result {
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--color-surface-muted);
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}

.review__result:last-child {
  border-bottom: none;
}

.review__ok {
  color: var(--color-success-text);
  font-weight: 600;
}

.review__no {
  color: var(--color-danger-text);
  font-weight: 600;
}
</style>
