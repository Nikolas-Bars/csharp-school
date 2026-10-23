<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import BaseCodeBlock from '@/components/ui/BaseCodeBlock.vue'
import { t, type MessageKey } from '@/i18n'
import type { CourseSummary } from '@/types/api'

/** Курс на главной по макету: название, пример из курса, описание, размер, первый урок и программа */
const { course } = defineProps<{ course: CourseSummary }>()

/** Пример на карточке — по курсу; у курса без примера карточка просто короче */
const PREVIEWS: Partial<Record<string, MessageKey>> = { csharp: 'home.preview.csharp' }

const preview = computed(() => PREVIEWS[course.slug] ?? null)
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
    <BaseCodeBlock v-if="preview" :code="t(preview)" />
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
