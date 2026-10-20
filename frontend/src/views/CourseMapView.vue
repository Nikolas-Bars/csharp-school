<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { coursesApi } from '@/api/courses'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useLoader } from '@/composables/useLoader'
import { t } from '@/i18n'

/** Карта курса по макету: модули и уроки по порядку, слева — с чего начать и цель курса */
const route = useRoute()
const courseSlug = computed(() => String(route.params.course))

const { data: course, error, loading } = useLoader(courseSlug, (slug) => coursesApi.course(slug))

const first = computed(() => {
  const module = course.value?.modules[0]
  const lesson = module?.lessons[0]
  return module && lesson ? { module, lesson } : null
})

function lessonRoute(module: string, lesson: string) {
  return { name: 'lesson', params: { course: courseSlug.value, module, lesson } }
}

function reload() {
  window.location.reload()
}
</script>

<template>
  <div class="map">
    <p v-if="loading" class="map__status" role="status">{{ t('course.loading') }}</p>
    <div v-else-if="error" class="map__status" role="alert">
      <p>{{ error.message }}</p>
      <BaseButton variant="ghost" @click="reload">{{ t('course.retry') }}</BaseButton>
    </div>

    <template v-else-if="course">
      <aside class="map__aside">
        <div v-if="first" class="map__card">
          <span class="map__eyebrow">{{ t('course.startLabel') }}</span>
          <p class="map__muted">{{ t('course.startLead') }}</p>
          <RouterLink class="map__cta" :to="lessonRoute(first.module.slug, first.lesson.slug)">
            {{ t('course.start', { title: first.lesson.title }) }}
          </RouterLink>
        </div>
        <div class="map__card map__card--dark">
          <span class="map__eyebrow map__eyebrow--light">{{ t('course.goalLabel') }}</span>
          <strong class="map__goal">{{ t('course.goalTitle') }}</strong>
          <span>{{ t('course.goalText') }}</span>
        </div>
      </aside>

      <main class="map__main">
        <section
          v-for="(module, moduleIndex) in course.modules"
          :key="module.slug"
          class="map__module"
        >
          <span class="map__eyebrow map__eyebrow--accent">{{
            t('course.module', { n: moduleIndex + 1 })
          }}</span>
          <h1 class="map__title">{{ module.title }}</h1>
          <p class="map__muted">{{ module.description }}</p>

          <ol class="map__lessons">
            <li v-for="(lesson, lessonIndex) in module.lessons" :key="lesson.slug">
              <RouterLink class="map__lesson" :to="lessonRoute(module.slug, lesson.slug)">
                <span class="map__lesson-head">
                  <span class="map__badge">{{
                    t('course.lessonBadge', { n: lessonIndex + 1 })
                  }}</span>
                  <span class="map__muted">
                    {{ lesson.tasks > 0 ? t('course.tasks', lesson.tasks) : t('course.noTasks') }}
                  </span>
                </span>
                <strong class="map__lesson-title">{{ lessonIndex + 1 }}. {{ lesson.title }}</strong>
              </RouterLink>
            </li>
          </ol>
        </section>
      </main>
    </template>
  </div>
</template>

<style scoped>
.map {
  max-width: 80rem;
  margin: 0 auto;
  padding: var(--space-6) var(--space-6) var(--space-7);
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-6);
  align-items: flex-start;
}

.map__status {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  align-items: flex-start;
}

.map__aside {
  flex: 1 1 18rem;
  max-width: 22rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.map__card {
  padding: var(--space-5);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.map__card--dark {
  border-color: var(--color-inverse);
  background: var(--color-inverse);
  color: var(--color-inverse-text);
}

.map__eyebrow {
  font-size: var(--font-size-sm);
  font-weight: 600;
  text-transform: uppercase;
  color: var(--color-text-muted);
}

.map__eyebrow--light {
  color: var(--color-inverse-text);
}

.map__eyebrow--accent {
  color: var(--color-accent-text);
}

.map__goal {
  font-size: var(--font-size-lg);
}

.map__cta {
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-primary);
  color: var(--color-on-accent);
  font-weight: 600;
  text-align: center;
  text-decoration: none;
}

.map__main {
  flex: 999 1 35rem;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.map__module {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.map__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.map__muted {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}

.map__lessons {
  margin: var(--space-3) 0 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
  gap: var(--space-3);
}

.map__lesson {
  min-height: 8.5rem;
  padding: var(--space-4);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
  text-decoration: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.map__lesson:hover {
  border-color: var(--color-primary);
}

.map__cta:focus-visible,
.map__lesson:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.map__lesson-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
}

.map__badge {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-pill);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.map__lesson-title {
  font-size: var(--font-size-lg);
  line-height: 1.35;
}
</style>
