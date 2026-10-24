<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import WordList, { type WordRow } from '@/components/vocabulary/WordList.vue'
import { useCourseWords } from '@/composables/useCourseWords'
import { t } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import { useVocabularyStore } from '@/stores/vocabulary'

/**
 * «Мой словарь» по макету: слова пройденных уроков, сколько ждут повторения сегодня, поиск и
 * фильтр. Гость видит, что словарь живёт в этом браузере.
 */
const route = useRoute()
const course = computed(() => String(route.params.course))

const auth = useAuthStore()
const vocabulary = useVocabularyStore()
const { loading, error, map, learned, placeOf, lessonLabel } = useCourseWords(course)

const now = new Date()
const due = computed(() => vocabulary.dueWords(now).length)

const rows = computed((): WordRow[] =>
  learned.value.map(({ card, entry }) => ({
    id: card.id,
    word: card.word,
    ipa: card.ipa,
    pos: card.pos,
    translation: card.translation,
    lesson: lessonLabel(card),
    module: placeOf(card)?.module ?? 0,
    box: entry.box,
    dueAt: entry.due_at,
  })),
)

const reviewRoute = computed(() => ({ name: 'review', params: { course: course.value } }))
const mapRoute = computed(() => ({ name: 'course', params: { course: course.value } }))
</script>

<template>
  <div class="dictionary">
    <p v-if="loading" role="status">{{ t('words.dictionary.loading') }}</p>
    <p v-else-if="error" role="alert">
      {{ error.message }} <RouterLink :to="mapRoute">{{ t('words.toCourse') }}</RouterLink>
    </p>

    <template v-else>
      <div class="dictionary__top">
        <div class="dictionary__intro">
          <span class="dictionary__eyebrow">{{ map?.title }}</span>
          <h1 class="dictionary__title">{{ t('words.dictionary.title') }}</h1>
          <p class="dictionary__lead">{{ t('words.dictionary.lead') }}</p>
        </div>
        <div v-if="rows.length > 0" class="dictionary__due">
          <div class="dictionary__due-count">
            <strong>{{ due }}</strong>
            <span>{{ t('words.dictionary.waiting', due) }}</span>
          </div>
          <RouterLink v-if="due > 0" class="dictionary__cta" :to="reviewRoute">
            {{ t('words.dictionary.review', due) }}
          </RouterLink>
          <span v-else class="dictionary__muted">{{ t('words.dictionary.allDone') }}</span>
        </div>
      </div>

      <p v-if="auth.status === 'guest'" class="dictionary__note">
        {{ t('words.dictionary.guest') }}
        <RouterLink :to="{ name: 'login', query: { redirect: route.fullPath } }">{{
          t('words.dictionary.login')
        }}</RouterLink>
      </p>

      <WordList v-if="rows.length > 0" :rows="rows" :now="now" />
      <div v-else class="dictionary__empty">
        <p>{{ t('words.dictionary.empty') }}</p>
        <RouterLink class="dictionary__cta" :to="mapRoute">{{ t('words.toCourse') }}</RouterLink>
      </div>
    </template>
  </div>
</template>

<style scoped>
.dictionary {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-4) var(--space-6) var(--space-7);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.dictionary__top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-5);
}

.dictionary__intro {
  max-width: 40rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.dictionary__eyebrow {
  font-size: var(--font-size-sm);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-text-muted);
}

.dictionary__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-title);
  line-height: 1.1;
}

.dictionary__lead {
  margin: 0;
  line-height: 1.6;
  color: var(--color-text-muted);
}

.dictionary__due {
  padding: var(--space-5);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-5);
}

.dictionary__due-count {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  font-variant-numeric: tabular-nums;
}

.dictionary__due-count strong {
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.dictionary__cta {
  min-height: 3rem;
  box-sizing: border-box;
  display: inline-flex;
  align-items: center;
  padding: var(--space-3) var(--space-5);
  border-radius: var(--radius);
  background: var(--color-primary);
  color: var(--color-on-accent);
  font-weight: 600;
  text-decoration: none;
}

.dictionary__cta:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.dictionary__muted {
  color: var(--color-text-muted);
}

.dictionary__note {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
}

.dictionary__empty {
  padding: var(--space-6) var(--space-5);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-4);
}

.dictionary__empty p {
  margin: 0;
}

@media (max-width: 40rem) {
  .dictionary {
    padding: var(--space-3) var(--space-4) var(--space-6);
  }

  .dictionary__title {
    font-size: var(--font-size-xxl);
  }
}
</style>
