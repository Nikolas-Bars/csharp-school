<script setup lang="ts">
import { computed, defineAsyncComponent, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { coursesApi } from '@/api/courses'
import ExerciseSet from '@/components/exercise/ExerciseSet.vue'
import LessonDone, { type LessonModuleResult } from '@/components/lesson/LessonDone.vue'
import LessonQuiz from '@/components/lesson/LessonQuiz.vue'
import MarkdownView from '@/components/lesson/MarkdownView.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import { useLoader } from '@/composables/useLoader'
import { t } from '@/i18n'
import { locale } from '@/i18n/locale'
import { useCourseStore } from '@/stores/course'
import { useProgressStore } from '@/stores/progress'
import type { LessonStep } from '@/types/api'

// Задача с кодом тянет редактор и мост к компилятору C# — урок английского их не грузит
const LessonTask = defineAsyncComponent(() => import('@/components/lesson/LessonTask.vue'))

/**
 * Урок: шаги по порядку — теория, задачи (C#), практика (английский), проверка знаний. Текущий
 * шаг — в адресе (?step=3), поэтому перезагрузка и «назад» в браузере работают как ожидается.
 */
const route = useRoute()
const router = useRouter()

const path = computed(() => ({
  course: String(route.params.course),
  module: String(route.params.module),
  lesson: String(route.params.lesson),
}))

// Ключ — строка: смена шага (?step=) не меняет урок и не должна загружать его заново
const key = computed(() => `${path.value.course}/${path.value.module}/${path.value.lesson}`)
const progress = useProgressStore()
// Урок и последний зачтённый код его задач (для вошедшего — с сервера, у гостя — из браузера).
// Код — после загрузки прогресса: только что вошедший гость сначала переносит свои решения
const { data, error, loading } = useLoader(key, async () => {
  const { course, module, lesson } = path.value
  const [content, solutions] = await Promise.all([
    coursesApi.lesson(course, module, lesson),
    progress.load(course).then(() => progress.solutions(course, module, lesson)),
  ])
  return { content, solutions }
})
const lesson = computed(() => data.value?.content ?? null)
const solutions = computed(() => data.value?.solutions ?? {})

const total = computed(() => lesson.value?.steps.length ?? 0)
const index = computed(() => {
  const requested = Number(route.query.step ?? 1) - 1
  return Number.isInteger(requested)
    ? Math.min(Math.max(requested, 0), Math.max(total.value - 1, 0))
    : 0
})
const step = computed(() => lesson.value?.steps[index.value] ?? null)

/** Шаги, пройденные в этот раз (и теория, которую прочитали) — для полосок */
const visited = ref(new Set<number>())

/** Засчитываемый шаг в прогрессе: id задачи или практики, «quiz»; теория не засчитывается */
function stepKey(item: LessonStep): string | null {
  return item.type === 'task' || item.type === 'practice'
    ? item.id
    : item.type === 'quiz'
      ? 'quiz'
      : null
}

function isCompleted(key: string): boolean {
  return progress.isCompleted(path.value.module, path.value.lesson, key)
}

/** Шаг пройден: задача, практика или проверка знаний — по прогрессу, теория — если её прочитали */
function isDone(itemIndex: number): boolean {
  const item = lesson.value?.steps[itemIndex]
  if (!item) {
    return false
  }
  if (visited.value.has(itemIndex)) {
    return true
  }
  const key = stepKey(item)
  return key !== null && isCompleted(key)
}

/** Задача решена или проверка знаний пройдена — в прогресс (у гостя — в браузер) */
function record(stepKey: string, code: string | null = null) {
  complete()
  void progress.complete(path.value.course, {
    module: path.value.module,
    lesson: path.value.lesson,
    step: stepKey,
    code,
  })
}

function goTo(target: number) {
  void router.push({ query: { ...route.query, step: target + 1 } })
}

function onTaskPassed(code: string) {
  if (step.value?.type === 'task') {
    record(step.value.id, code)
  }
}

function complete() {
  visited.value = new Set(visited.value).add(index.value)
}

function next() {
  complete()
  if (index.value < total.value - 1) {
    goTo(index.value + 1)
  }
}

const mapRoute = computed(() => ({ name: 'course', params: { course: path.value.course } }))

/**
 * Карта курса — чтобы узнать, контрольная ли это и какой модуль следующий. Хранится в сторе
 * (её же берёт карта), загружается на текущем языке и не мешает уроку: не загрузилась —
 * после вопросов просто ссылка на карту.
 */
const courses = useCourseStore()
watch(
  () => [path.value.course, locale.value] as const,
  ([slug]) => {
    courses.load(slug).catch(() => {})
  },
  { immediate: true },
)
const courseMap = computed(() => courses.map(path.value.course))

function lessonRoute(module: string, lessonSlug: string, step?: number) {
  return {
    name: 'lesson',
    params: { course: path.value.course, module, lesson: lessonSlug },
    query: step === undefined ? {} : { step },
  }
}

/**
 * Непройденные задачи и практики этого урока: номер шага с 1 и подпись ссылки — без них урок не
 * пройден, даже если проверка знаний сдана
 */
const unsolvedSteps = computed(() =>
  (lesson.value?.steps ?? []).flatMap((item, itemIndex) => {
    if (item.type === 'task' && !isCompleted(item.id)) {
      return [{ step: itemIndex + 1, label: t('quiz.toTask', { title: item.title }) }]
    }
    if (item.type === 'practice' && !isCompleted(item.id)) {
      return [{ step: itemIndex + 1, label: t('quiz.toPractice') }]
    }
    return []
  }),
)
const firstUnsolved = computed(() => {
  const first = unsolvedSteps.value[0]
  return first ? { label: first.label, to: { query: { ...route.query, step: first.step } } } : null
})

/** Засчитываемые шаги — по самому уроку: карта в памяти могла устареть после course:import */
const countedSteps = computed(() =>
  (lesson.value?.steps ?? []).flatMap((item) => {
    const key = stepKey(item)
    return key === null ? [] : [key]
  }),
)

/** Урок — контрольная модуля: после её вопросов — итог модуля и куда идти дальше */
const moduleResult = computed((): LessonModuleResult | null => {
  if (!courseMap.value || !lesson.value) {
    return null
  }
  // Этот урок — с его настоящими шагами
  const modules = courseMap.value.modules.map((item) =>
    item.slug !== path.value.module
      ? item
      : {
          ...item,
          lessons: item.lessons.map((summary) =>
            summary.slug === path.value.lesson
              ? { ...summary, steps: countedSteps.value }
              : summary,
          ),
        },
  )
  const index = modules.findIndex((item) => item.slug === path.value.module)
  const module = modules[index]
  if (!module || module.lessons.find((item) => item.slug === path.value.lesson)?.kind !== 'test') {
    return null
  }
  const states = modules.map((item) => progress.moduleProgress(item))
  const state = states[index]!

  // Следующий модуль — ближайший с уроками, с его первого непройденного урока
  const followingIndex = modules.findIndex((item, at) => at > index && item.lessons.length > 0)
  const following = modules[followingIndex]
  const nextLesson = following ? (states[followingIndex]!.next ?? following.lessons[0]) : undefined
  // Модуль не пройден — первый непройденный урок модуля (сама контрольная — ссылкой на задачи);
  // пройден — первый непройденный урок курса
  const resumeIndex = state.complete ? states.findIndex((item) => item.next !== null) : index
  const resumeLesson = states[resumeIndex]?.next
  const unsolved = unsolvedSteps.value[0]

  return {
    module: module.title,
    left: state.total - state.done,
    courseDone: modules.every((item, at) => item.lessons.length === 0 || states[at]!.complete),
    next:
      following && nextLesson
        ? { title: following.title, to: lessonRoute(following.slug, nextLesson.slug) }
        : null,
    resume:
      resumeLesson && resumeLesson.slug !== path.value.lesson
        ? {
            title: resumeLesson.title,
            to: lessonRoute(modules[resumeIndex]!.slug, resumeLesson.slug),
          }
        : null,
    tasks: unsolved ? lessonRoute(module.slug, path.value.lesson, unsolved.step) : null,
  }
})

const draftKey = (id: string) =>
  `${path.value.course}/${path.value.module}/${path.value.lesson}/${id}`
</script>

<template>
  <div class="lesson">
    <p v-if="loading" class="lesson__status" role="status">{{ t('lesson.loading') }}</p>
    <p v-else-if="error" class="lesson__status" role="alert">
      {{ error.message }}
      <RouterLink :to="mapRoute">{{ t('lesson.back') }}</RouterLink>
    </p>

    <template v-else-if="lesson && step">
      <header class="lesson__bar">
        <RouterLink class="lesson__back" :to="mapRoute" :aria-label="t('lesson.back')">
          <BaseIcon name="chevron-left" />
        </RouterLink>
        <div class="lesson__heading">
          <span class="lesson__module">{{ lesson.module.title }}</span>
          <h1 class="lesson__title">{{ lesson.title }}</h1>
        </div>
        <nav class="lesson__steps" :aria-label="t('lesson.step', { current: index + 1, total })">
          <button
            v-for="(item, itemIndex) in lesson.steps"
            :key="itemIndex"
            type="button"
            class="lesson__step"
            :class="{
              'lesson__step--done': isDone(itemIndex),
              'lesson__step--current': itemIndex === index,
            }"
            :aria-current="itemIndex === index ? 'step' : undefined"
            :aria-label="
              t(isDone(itemIndex) ? 'lesson.goToDoneStep' : 'lesson.goToStep', {
                n: itemIndex + 1,
                kind: t(`lesson.stepKind.${item.type}`),
              })
            "
            @click="goTo(itemIndex)"
          ></button>
          <span class="lesson__step-label">{{
            t('lesson.step', { current: index + 1, total })
          }}</span>
        </nav>
      </header>

      <main class="lesson__body">
        <article v-if="step.type === 'theory'" :key="`theory-${index}`" class="lesson__theory">
          <MarkdownView :source="step.text" />
          <BaseButton v-if="index < total - 1" class="lesson__next" @click="next">
            {{ t('lesson.next') }}
          </BaseButton>
        </article>

        <LessonTask
          v-else-if="step.type === 'task'"
          :key="step.id"
          :task="step"
          :draft-key="draftKey(step.id)"
          :solution="solutions[step.id]"
          @passed="onTaskPassed"
          @next="next"
        />

        <ExerciseSet
          v-else-if="step.type === 'practice'"
          :key="step.id"
          :exercises="step.exercises"
          :completed="isCompleted(step.id)"
          @done="record(step.id)"
        >
          <template #done="{ restart }">
            <div class="lesson__practice-done">
              <h2 class="lesson__practice-title">{{ t('practice.done') }}</h2>
              <p class="lesson__practice-text">{{ t('practice.doneText') }}</p>
              <div class="lesson__practice-actions">
                <BaseButton v-if="index < total - 1" variant="success" @click="next">
                  {{ t('practice.next') }}
                </BaseButton>
                <BaseButton variant="ghost" @click="restart">{{ t('practice.again') }}</BaseButton>
              </div>
            </div>
          </template>
        </ExerciseSet>

        <ExerciseSet
          v-else-if="'exercises' in step"
          :key="`quiz-${index}`"
          :exercises="step.exercises"
          :completed="isCompleted('quiz')"
          @done="record('quiz')"
        >
          <template #header>
            <h2 class="lesson__quiz-title">{{ t('quiz.title') }}</h2>
            <p class="lesson__quiz-lead">{{ t('quiz.lead') }}</p>
          </template>
          <template #done="{ restart }">
            <p class="lesson__quiz-done" role="status">
              <strong>{{
                unsolvedSteps.length > 0
                  ? t('quiz.doneStepsLeft', { n: unsolvedSteps.length })
                  : t('quiz.done')
              }}</strong>
            </p>
            <LessonDone :result="moduleResult" :unsolved="firstUnsolved" :map="mapRoute" />
            <BaseButton class="lesson__again" variant="ghost" @click="restart">
              {{ t('practice.again') }}
            </BaseButton>
          </template>
        </ExerciseSet>

        <LessonQuiz
          v-else
          :key="`quiz-${index}`"
          :questions="step.questions"
          :unsolved="unsolvedSteps.length"
          @done="record('quiz')"
        >
          <template #done>
            <LessonDone :result="moduleResult" :unsolved="firstUnsolved" :map="mapRoute" />
          </template>
        </LessonQuiz>
      </main>
    </template>
  </div>
</template>

<style scoped>
.lesson {
  display: flex;
  flex-direction: column;
}

.lesson__status {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.lesson__bar {
  padding: var(--space-3) var(--space-5);
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-3) var(--space-4);
}

.lesson__back {
  width: 2.75rem;
  height: 2.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  color: var(--color-text);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.lesson__heading {
  flex: 1 1 12rem;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.lesson__module {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.lesson__title {
  margin: 0;
  font-size: var(--font-size-lg);
}

.lesson__steps {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}

/* Полоска шага — кнопка 44 × 44 px; сама полоска тонкая, промежуток между полосками — внутри кнопки */
.lesson__step {
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  border: none;
  background: linear-gradient(var(--color-border), var(--color-border)) center /
    calc(100% - var(--space-1) * 2) 0.5rem no-repeat;
  cursor: pointer;
}

.lesson__step--done {
  background-image: linear-gradient(var(--color-success), var(--color-success));
}

.lesson__step--current {
  background-image: linear-gradient(var(--color-primary), var(--color-primary));
}

.lesson__step-label {
  margin-left: var(--space-2);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.lesson__back:focus-visible,
.lesson__step:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.lesson__body {
  width: 100%;
  max-width: 90rem;
  margin: 0 auto;
  padding: var(--space-5) var(--space-5) var(--space-7);
}

.lesson__theory {
  max-width: 44rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.lesson__next {
  align-self: flex-start;
}

.lesson__practice-done {
  max-width: 44rem;
  padding: var(--space-6);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.lesson__practice-title,
.lesson__quiz-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.lesson__practice-title {
  color: var(--color-success-text);
}

.lesson__practice-text,
.lesson__quiz-lead {
  margin: 0;
  color: var(--color-text-muted);
}

.lesson__practice-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.lesson__again {
  align-self: flex-start;
}

.lesson__quiz-done {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-success-soft);
  color: var(--color-success-text);
}
</style>
