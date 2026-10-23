<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import BaseCodeBlock from '@/components/ui/BaseCodeBlock.vue'
import BaseSpeakButton from '@/components/ui/BaseSpeakButton.vue'
import { t, type MessageKey } from '@/i18n'
import type { CourseSummary } from '@/types/api'

/** Курс на главной по макету: название, пример из курса, описание, размер, первый урок и программа */
const { course } = defineProps<{ course: CourseSummary }>()

/** Пример на карточке — по курсу: код C# или фраза английского со звуком; без примера карточка короче */
const CODE: Partial<Record<string, MessageKey>> = { csharp: 'home.preview.csharp' }
const PHRASES: Partial<
  Record<string, { label: MessageKey; phrase: MessageKey; translation: MessageKey }>
> = {
  english: {
    label: 'home.preview.english.label',
    phrase: 'home.preview.english.phrase',
    translation: 'home.preview.english.translation',
  },
}

const code = computed(() => CODE[course.slug] ?? null)
const phrase = computed(() => PHRASES[course.slug] ?? null)
const size = computed(
  () => `${t('home.modules', course.modules)} · ${t('home.lessons', course.lessons)}`,
)
</script>

<template>
  <article class="card" :aria-labelledby="`course-${course.slug}`">
    <div class="card__head">
      <h2 :id="`course-${course.slug}`" class="card__title">{{ course.title }}</h2>
      <span class="card__size">{{ size }}</span>
    </div>
    <BaseCodeBlock v-if="code" :code="t(code)" />
    <div v-else-if="phrase" class="card__phrase">
      <span class="card__phrase-text">
        <span class="card__phrase-label">{{ t(phrase.label) }}</span>
        <strong lang="en">{{ t(phrase.phrase) }}</strong>
        <span class="card__phrase-translation">{{ t(phrase.translation) }}</span>
      </span>
      <BaseSpeakButton :text="t(phrase.phrase)" />
    </div>
    <p class="card__text">{{ course.description }}</p>
    <div class="card__actions">
      <RouterLink
        v-if="course.start"
        class="card__primary"
        :to="{
          name: 'lesson',
          params: { course: course.slug, module: course.start.module, lesson: course.start.lesson },
        }"
      >
        {{ t('home.start') }}
      </RouterLink>
      <RouterLink class="card__secondary" :to="{ name: 'course', params: { course: course.slug } }">
        {{ t('home.program') }}
      </RouterLink>
    </div>
  </article>
</template>

<style scoped>
.card__phrase {
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius-lg);
  background: var(--color-background);
  display: flex;
  align-items: center;
  gap: var(--space-4);
}

.card__phrase-text {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  font-size: var(--font-size-lg);
}

.card__phrase-label {
  font-size: var(--font-size-sm);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-text-muted);
}

.card__phrase-translation {
  font-size: var(--font-size-md);
  color: var(--color-text-muted);
}

.card {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
  min-width: 0;
  padding: var(--space-6);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.card__head {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.card__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
  line-height: 1.2;
  text-wrap: balance;
}

.card__size {
  align-self: flex-start;
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-pill);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.card__text {
  margin: 0;
  color: var(--color-text-muted);
  line-height: 1.6;
}

.card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-top: auto;
}

.card__primary,
.card__secondary {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-5);
  border-radius: var(--radius);
  font-weight: 600;
  text-decoration: none;
}

.card__primary {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.card__primary:hover {
  background: var(--color-primary-hover);
}

.card__secondary {
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
}

.card__secondary:hover {
  background: var(--color-surface-muted);
}

.card__primary:focus-visible,
.card__secondary:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
</style>
