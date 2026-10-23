<script setup lang="ts">
import { computed, ref } from 'vue'

import { t } from '@/i18n'
import type { Exercise } from '@/types/api'

import ExerciseCard from './ExerciseCard.vue'

/**
 * Набор упражнений по одному — практика урока английского и его проверка знаний (квиз): точки
 * прогресса, «Задание N из M», после последнего — итог (`#done`). Пройденный раньше набор сразу
 * показывает итог: пройти ещё раз можно, но не обязательно.
 */
const { exercises, completed = false } = defineProps<{
  exercises: Exercise[]
  /** Набор уже засчитан в прогрессе */
  completed?: boolean
}>()
const emit = defineEmits<{ done: [] }>()

const index = ref(completed ? exercises.length : 0)
/** Номер прохода: «Пройти ещё раз» начинает упражнения с чистого листа */
const round = ref(0)

const finished = computed(() => index.value >= exercises.length)
const current = computed(() => exercises[index.value] ?? null)

function next() {
  index.value++
  if (finished.value) {
    emit('done')
  }
}

function restart() {
  index.value = 0
  round.value++
}

const counter = computed(() =>
  t('exercise.counter', {
    current: Math.min(index.value + 1, exercises.length),
    total: exercises.length,
  }),
)
</script>

<template>
  <section class="set">
    <slot name="header" />

    <div class="set__progress">
      <span class="set__kind">{{
        current ? t(`exercise.kind.${current.kind}`) : t('exercise.done')
      }}</span>
      <div class="set__dots">
        <span
          v-for="(_, dot) in exercises"
          :key="dot"
          class="set__dot"
          :class="{
            'set__dot--done': dot < index,
            'set__dot--current': dot === index,
          }"
          aria-hidden="true"
        ></span>
        <span class="set__counter">{{ counter }}</span>
      </div>
    </div>

    <ExerciseCard
      v-if="current"
      :key="`${round}-${index}`"
      :exercise="current"
      :focus-on-mount="index > 0 || round > 0"
      @next="next"
    />
    <slot v-else name="done" :restart="restart" />
  </section>
</template>

<style scoped>
.set {
  max-width: 44rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.set__progress {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.set__kind {
  font-size: var(--font-size-sm);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-accent-text);
}

.set__dots {
  display: flex;
  align-items: center;
  gap: var(--space-1);
}

.set__dot {
  width: 1.75rem;
  height: 0.5rem;
  border-radius: var(--radius-pill);
  background: var(--color-border);
}

.set__dot--done {
  background: var(--color-success);
}

.set__dot--current {
  background: var(--color-primary);
}

.set__counter {
  margin-left: var(--space-2);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}
</style>
