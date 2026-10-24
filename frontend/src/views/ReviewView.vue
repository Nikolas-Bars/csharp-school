<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import ReviewSession from '@/components/vocabulary/ReviewSession.vue'
import { useCourseWords } from '@/composables/useCourseWords'
import { t } from '@/i18n'
import { useVocabularyStore } from '@/stores/vocabulary'
import type { CourseWord } from '@/types/api'

/**
 * Повторение слов, которые ждут сегодня. Набор слов берётся один раз в начале: ответ отодвигает
 * слово в расписании, но из повторения оно не пропадает. Ответы уходят на сервер пачкой, а при
 * уходе со страницы — сразу.
 */
const route = useRoute()
const course = computed(() => String(route.params.course))

const vocabulary = useVocabularyStore()
const { loading, error, cards, lessonLabel } = useCourseWords(course)

/** Слов за раз — не больше: длинное повторение бросают на середине */
const SESSION = 20

const session = ref<CourseWord[] | null>(null)
watch(
  loading,
  (value) => {
    if (value || error.value || session.value !== null) {
      return
    }
    const byId = new Map(cards.value.map((card) => [card.id, card]))
    session.value = vocabulary
      .dueWords()
      .flatMap((id) => byId.get(id) ?? [])
      .slice(0, SESSION)
  },
  { immediate: true },
)

function answer(word: string, remembered: boolean) {
  vocabulary.review(course.value, word, remembered)
}

onBeforeUnmount(() => {
  void vocabulary.sync(course.value)
})

const dictionaryRoute = computed(() => ({ name: 'vocabulary', params: { course: course.value } }))
</script>

<template>
  <div class="review-page">
    <header class="review-page__bar">
      <RouterLink
        class="review-page__close"
        :to="dictionaryRoute"
        :aria-label="t('words.review.close')"
      >
        <BaseIcon name="close" />
      </RouterLink>
      <div class="review-page__heading">
        <span class="review-page__muted">{{ t('words.dictionary.title') }}</span>
        <h1 class="review-page__title">{{ t('words.review.title') }}</h1>
      </div>
    </header>

    <p v-if="loading" role="status">{{ t('words.dictionary.loading') }}</p>
    <p v-else-if="error" role="alert">{{ error.message }}</p>
    <div v-else-if="session && session.length === 0" class="review-page__empty">
      <p>{{ t('words.review.nothing') }}</p>
      <RouterLink class="review-page__link" :to="dictionaryRoute">{{
        t('words.review.toDictionary')
      }}</RouterLink>
    </div>
    <ReviewSession
      v-else-if="session"
      :words="session"
      :lesson-label="lessonLabel"
      @answer="answer"
    >
      <template #done="{ restart }">
        <RouterLink class="review-page__link" :to="dictionaryRoute">{{
          t('words.review.toDictionary')
        }}</RouterLink>
        <BaseButton variant="ghost" @click="restart">{{ t('words.review.again') }}</BaseButton>
      </template>
    </ReviewSession>
  </div>
</template>

<style scoped>
.review-page {
  max-width: 40rem;
  margin: 0 auto;
  padding: var(--space-4) var(--space-4) var(--space-7);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.review-page__bar {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.review-page__close {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text);
}

.review-page__close:focus-visible,
.review-page__link:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.review-page__heading {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.review-page__muted {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.review-page__title {
  margin: 0;
  font-size: var(--font-size-lg);
}

.review-page__empty {
  padding: var(--space-6) var(--space-5);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-4);
}

.review-page__empty p {
  margin: 0;
}

.review-page__link {
  min-height: 3rem;
  box-sizing: border-box;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-3) var(--space-5);
  border-radius: var(--radius);
  background: var(--color-primary);
  color: var(--color-on-accent);
  font-weight: 600;
  text-decoration: none;
}
</style>
