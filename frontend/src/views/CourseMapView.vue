<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import { useLoader } from '@/composables/useLoader'
import { t } from '@/i18n'
import { locale } from '@/i18n/locale'
import { useCourseStore } from '@/stores/course'
import { useProgressStore, type ModuleProgress } from '@/stores/progress'
import type { LessonSummary } from '@/types/api'

/**
 * Карта курса по макету: модули и уроки по порядку, слева — с чего начать и цель курса.
 * У модуля — сколько уроков пройдено и «Модуль пройден» после всех уроков вместе с контрольной.
 */
const route = useRoute()
const courseSlug = computed(() => String(route.params.course))

const progress = useProgressStore()
const courses = useCourseStore()
// Заново — при смене курса и языка; карта из памяти сразу показывается и обновляется в фоне
const {
  data: loaded,
  error,
  loading,
} = useLoader(
  () => `${courseSlug.value}|${locale.value}`,
  () => {
    void progress.load(courseSlug.value)
    return courses.load(courseSlug.value, { refresh: true })
  },
)
/** Обновлённая в фоне карта заменяет показанную */
const course = computed(() => courses.map(courseSlug.value) ?? loaded.value)

/** Все уроки курса по порядку — с модулем и состоянием */
const lessons = computed(() =>
  (course.value?.modules ?? []).flatMap((module) =>
    module.lessons.map((lesson) => ({
      module,
      lesson,
      done: progress.isLessonDone(module.slug, lesson.slug, lesson.steps),
    })),
  ),
)
const doneCount = computed(() => lessons.value.filter((item) => item.done).length)
/** «Сейчас» — первый непройденный урок: с него и продолжать */
const current = computed(() => lessons.value.find((item) => !item.done) ?? null)

type LessonState = 'done' | 'current' | 'open'

/** Состояние каждого урока «модуль/урок» — считается один раз на изменение прогресса */
const states = computed(
  () =>
    new Map<string, LessonState>(
      lessons.value.map((item) => [
        `${item.module.slug}/${item.lesson.slug}`,
        item.done ? 'done' : item === current.value ? 'current' : 'open',
      ]),
    ),
)

/** Прогресс каждого модуля: «пройдено N из M» и «Модуль пройден» */
const modules = computed(
  () =>
    new Map<string, ModuleProgress>(
      (course.value?.modules ?? []).map((module) => [module.slug, progress.moduleProgress(module)]),
    ),
)

function moduleState(module: string): ModuleProgress {
  return modules.value.get(module) ?? { done: 0, total: 0, complete: false, next: null }
}

/** Подпись состояния на карточке; у контрольной вместо «Урок N» — только «Контрольная» */
function badge(module: string, lesson: LessonSummary, index: number): string | null {
  const value = state(module, lesson.slug)
  if (value === 'done') {
    return t('course.done')
  }
  if (value === 'current') {
    return t('course.current')
  }
  return lesson.kind === 'test' ? null : t('course.lessonBadge', { n: index + 1 })
}

function state(module: string, lesson: string): LessonState {
  return states.value.get(`${module}/${lesson}`) ?? 'open'
}

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
        <div class="map__card">
          <span class="map__eyebrow">{{
            doneCount > 0 ? t('course.progressLabel') : t('course.startLabel')
          }}</span>
          <template v-if="doneCount > 0">
            <strong class="map__count">{{ doneCount }} / {{ lessons.length }}</strong>
            <div
              class="map__bar"
              role="progressbar"
              :aria-valuenow="doneCount"
              aria-valuemin="0"
              :aria-valuemax="lessons.length"
              :aria-label="t('course.lessonsDone')"
            >
              <span
                class="map__bar-fill"
                :style="{ width: `${(doneCount / lessons.length) * 100}%` }"
              ></span>
            </div>
            <span class="map__muted">{{ t('course.lessonsDone') }}</span>
          </template>
          <p v-else class="map__muted">{{ t('course.startLead') }}</p>
          <RouterLink
            v-if="current"
            class="map__cta"
            :to="lessonRoute(current.module.slug, current.lesson.slug)"
          >
            {{
              doneCount > 0
                ? t('course.continue', { title: current.lesson.title })
                : t('course.start', { title: current.lesson.title })
            }}
          </RouterLink>
          <strong v-else class="map__finished">{{ t('course.finished') }}</strong>
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
          :class="{ 'map__module--done': moduleState(module.slug).complete }"
          :aria-labelledby="`module-${module.slug}`"
        >
          <span class="map__module-head">
            <span class="map__eyebrow map__eyebrow--accent">{{
              t('course.module', { n: moduleIndex + 1 })
            }}</span>
            <span v-if="moduleState(module.slug).complete" class="map__badge map__badge--done">
              <BaseIcon name="check" :size="16" />
              {{ t('course.moduleDone') }}
            </span>
          </span>
          <h1 :id="`module-${module.slug}`" class="map__title">{{ module.title }}</h1>
          <p class="map__muted">{{ module.description }}</p>
          <p class="map__module-progress">
            {{
              t('course.moduleProgress', {
                done: moduleState(module.slug).done,
                total: moduleState(module.slug).total,
              })
            }}
          </p>

          <ol class="map__lessons">
            <li v-for="(lesson, lessonIndex) in module.lessons" :key="lesson.slug">
              <RouterLink
                class="map__lesson"
                :class="`map__lesson--${state(module.slug, lesson.slug)}`"
                :to="lessonRoute(module.slug, lesson.slug)"
                :aria-current="state(module.slug, lesson.slug) === 'current' ? 'step' : undefined"
              >
                <span class="map__lesson-head">
                  <span class="map__badges">
                    <span
                      v-if="badge(module.slug, lesson, lessonIndex)"
                      class="map__badge"
                      :class="`map__badge--${state(module.slug, lesson.slug)}`"
                    >
                      {{ badge(module.slug, lesson, lessonIndex) }}
                    </span>
                    <span v-if="lesson.kind === 'test'" class="map__badge map__badge--test">
                      {{ t('course.test') }}
                    </span>
                  </span>
                  <span class="map__muted">
                    {{ lesson.tasks > 0 ? t('course.tasks', lesson.tasks) : t('course.noTasks') }}
                  </span>
                </span>
                <strong class="map__lesson-title">{{
                  lesson.kind === 'test' ? lesson.title : `${lessonIndex + 1}. ${lesson.title}`
                }}</strong>
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

.map__module-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2) var(--space-3);
}

.map__module-progress {
  margin: 0;
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--color-text-muted);
}

.map__module--done .map__module-progress {
  color: var(--color-success-text);
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

.map__badges {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.map__badge {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-pill);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.map__lesson--current {
  /* Рамка толще без сдвига карточки: вторая линия — тенью */
  border-color: var(--color-primary);
  box-shadow:
    0 0 0 1px var(--color-primary),
    var(--shadow);
}

.map__badge--done {
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.map__badge--test {
  background: var(--color-warning-soft);
  color: var(--color-warning-text);
}

.map__badge--current {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.map__count {
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.map__bar {
  height: 0.625rem;
  border-radius: var(--radius-pill);
  background: var(--color-primary-soft);
  overflow: hidden;
}

.map__bar-fill {
  display: block;
  height: 100%;
  border-radius: var(--radius-pill);
  background: var(--color-primary);
}

.map__finished {
  color: var(--color-success-text);
}

.map__lesson-title {
  font-size: var(--font-size-lg);
  line-height: 1.35;
}
</style>
