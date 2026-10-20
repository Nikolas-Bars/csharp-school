<script setup lang="ts">
import { RouterLink } from 'vue-router'

import { t } from '@/i18n'
import { MAIN_COURSE } from '@/utils/course'

/** Первый урок курса: модуль и урок — по content/ (01-first-programs/01-hello) */
const firstLesson = {
  name: 'lesson',
  params: { course: MAIN_COURSE, module: '01-first-programs', lesson: '01-hello' },
}
</script>

<template>
  <main class="home">
    <span class="home__badge">{{ t('home.badge') }}</span>
    <h1 class="home__title">{{ t('home.title') }}</h1>
    <p class="home__lead">{{ t('home.lead') }}</p>
    <div class="home__actions">
      <RouterLink class="home__primary" :to="firstLesson">{{ t('home.start') }}</RouterLink>
      <RouterLink class="home__secondary" :to="{ name: 'course', params: { course: MAIN_COURSE } }">
        {{ t('home.program') }}
      </RouterLink>
    </div>
  </main>
</template>

<style scoped>
.home {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-7) var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.home__badge {
  align-self: flex-start;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-pill);
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
  font-weight: 600;
  font-size: var(--font-size-sm);
}

.home__title {
  margin: 0;
  max-width: 45rem;
  font-family: var(--font-display);
  font-size: clamp(var(--font-size-xxl), 6vw, var(--font-size-display));
  line-height: 1.1;
}

.home__lead {
  margin: 0;
  max-width: 35rem;
  font-size: var(--font-size-lg);
  color: var(--color-text-muted);
}

.home__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

/* Кнопки-ссылки по макету: главная — акцентная, вторая — белая с рамкой */
.home__primary,
.home__secondary {
  min-height: 2.75rem;
  padding: var(--space-4) var(--space-6);
  border-radius: var(--radius-lg);
  font-weight: 600;
  font-size: var(--font-size-lg);
  text-decoration: none;
}

.home__primary {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.home__secondary {
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
}

.home__primary:focus-visible,
.home__secondary:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
</style>
