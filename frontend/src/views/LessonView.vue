<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { coursesApi } from '@/api/courses'
import LessonQuiz from '@/components/lesson/LessonQuiz.vue'
import LessonTask from '@/components/lesson/LessonTask.vue'
import MarkdownView from '@/components/lesson/MarkdownView.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import { useLoader } from '@/composables/useLoader'
import { t } from '@/i18n'

/**
 * Урок: шаги по порядку — теория, задачи, проверка знаний. Текущий шаг — в адресе (?step=3),
 * поэтому перезагрузка и «назад» в браузере работают как ожидается.
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
const {
  data: lesson,
  error,
  loading,
} = useLoader(key, () => coursesApi.lesson(path.value.course, path.value.module, path.value.lesson))

const total = computed(() => lesson.value?.steps.length ?? 0)
const index = computed(() => {
  const requested = Number(route.query.step ?? 1) - 1
  return Number.isInteger(requested)
    ? Math.min(Math.max(requested, 0), Math.max(total.value - 1, 0))
    : 0
})
const step = computed(() => lesson.value?.steps[index.value] ?? null)

/** Пройденные в этот раз шаги — для полосок прогресса. Сохранение прогресса — модуль Progress */
const done = ref(new Set<number>())

function goTo(target: number) {
  void router.push({ query: { ...route.query, step: target + 1 } })
}

function complete() {
  done.value = new Set(done.value).add(index.value)
}

function next() {
  complete()
  if (index.value < total.value - 1) {
    goTo(index.value + 1)
  }
}

const mapRoute = computed(() => ({ name: 'course', params: { course: path.value.course } }))
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
              'lesson__step--done': done.has(itemIndex),
              'lesson__step--current': itemIndex === index,
            }"
            :aria-current="itemIndex === index ? 'step' : undefined"
            :aria-label="
              t('lesson.goToStep', { n: itemIndex + 1, kind: t(`lesson.stepKind.${item.type}`) })
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
          @passed="complete"
          @next="next"
        />

        <LessonQuiz v-else :key="`quiz-${index}`" :questions="step.questions" @done="complete">
          <template #done>
            <RouterLink class="lesson__map-link" :to="mapRoute">{{ t('quiz.toMap') }}</RouterLink>
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
  gap: var(--space-1);
}

/* Полоска шага — кнопка; область нажатия 44 px по высоте, сама полоска тонкая */
.lesson__step {
  width: 2.25rem;
  height: 2.75rem;
  padding: 0;
  border: none;
  background: linear-gradient(var(--color-border), var(--color-border)) center / 100% 0.5rem
    no-repeat;
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
.lesson__step:focus-visible,
.lesson__map-link:focus-visible {
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

.lesson__map-link {
  align-self: flex-start;
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-5);
  border-radius: var(--radius);
  background: var(--color-success);
  color: var(--color-on-accent);
  font-weight: 600;
  text-decoration: none;
}
</style>
