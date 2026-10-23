<script setup lang="ts">
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue'

import BaseButton from '@/components/ui/BaseButton.vue'
import { t } from '@/i18n'
import type { Exercise } from '@/types/api'
import { checkAnswer, shownAnswer, type Answer, type Verdict } from '@/utils/answers'

import ExerciseChoice from './ExerciseChoice.vue'
import ExerciseFix from './ExerciseFix.vue'
import ExerciseGap from './ExerciseGap.vue'
import ExerciseOrder from './ExerciseOrder.vue'
import ExerciseTranslate from './ExerciseTranslate.vue'
import type { Tone } from './tone'

/**
 * Одно упражнение: ответ → «Проверить» → верно (объяснение и «Дальше») или ошибка с объяснением
 * именно этой ошибки. После второй неудачи показывается правильный ответ — его нужно выбрать или
 * ввести самому, чтобы пройти дальше. Проверка — в браузере (utils/answers.ts).
 *
 * Фокус для клавиатуры и диктора не теряется: новое упражнение — фокус на задание, верный ответ —
 * на «Дальше». Тот же неверный ответ второй раз не проверяется: случайный двойной Enter не
 * показывает готовый ответ.
 */
const { exercise, focusOnMount = false } = defineProps<{
  exercise: Exercise
  /** Пришли с прошлого упражнения: фокус — на задание, а не в начало страницы */
  focusOnMount?: boolean
}>()
const emit = defineEmits<{ next: [] }>()

/** До второй неудачи — пробуем сами, после неё — правильный ответ на экране */
const SHOW_AFTER = 2

const option = ref<number | null>(null)
const text = ref('')
const placed = ref<number[]>([])
const word = ref<number | null>(null)

const attempts = ref(0)
const verdict = ref<Verdict | null>(null)

const right = computed(() => verdict.value?.right === true)
const shown = computed(() => !right.value && attempts.value >= SHOW_AFTER)
const tone = computed<Tone>(() =>
  verdict.value === null ? 'idle' : verdict.value.right ? 'right' : 'wrong',
)

/** Ответ для проверки; null — ещё нечего проверять (кнопка неактивна) */
const answer = computed<Answer | null>(() => {
  switch (exercise.kind) {
    case 'choice':
      return option.value === null ? null : { option: option.value }
    case 'order':
      return placed.value.length === 0
        ? null
        : { text: placed.value.map((index) => exercise.tiles[index] ?? '').join(' ') }
    case 'fix':
      return word.value === null || text.value.trim() === ''
        ? null
        : { word: word.value, text: text.value }
    default:
      return text.value.trim() === '' ? null : { text: text.value }
  }
})

/** Объяснение неверного ответа: эта ошибка, иначе подсказка без готового ответа */
const why = computed(() => {
  const result = verdict.value
  if (!result || result.right) {
    return ''
  }
  if (result.wrongWord) {
    return t('exercise.wrongWord')
  }
  if (result.mistake) {
    return result.mistake
  }
  return exercise.kind === 'choice' ? t('exercise.wrongOption') : exercise.hint
})

const prompt = useTemplateRef<HTMLElement>('prompt')
const nextButton = useTemplateRef<InstanceType<typeof BaseButton>>('nextButton')

onMounted(() => {
  if (focusOnMount) {
    prompt.value?.focus()
  }
})

/** Проверить ответ; уже проверенный и не изменённый — не проверяется второй раз */
async function check() {
  if (answer.value === null || verdict.value !== null) {
    return
  }
  verdict.value = checkAnswer(exercise, answer.value)
  if (!verdict.value.right) {
    attempts.value++
    return
  }
  await nextTick()
  ;(nextButton.value?.$el as HTMLElement | undefined)?.focus()
}

/** Ответ поменяли после ошибки — снова «как будто не проверяли», объяснение остаётся до проверки */
function edited() {
  if (verdict.value && !verdict.value.right) {
    verdict.value = null
  }
}

const locked = computed(() => right.value)
const kindLabel = computed(() => t(`exercise.kind.${exercise.kind}`))
const shownOption = computed(() =>
  exercise.kind === 'choice' && shown.value ? exercise.answer : null,
)
</script>

<template>
  <form class="exercise" @submit.prevent="check">
    <div class="exercise__card">
      <h2 ref="prompt" class="exercise__prompt" tabindex="-1">
        <span class="visually-hidden">{{ kindLabel }}: </span>{{ exercise.prompt }}
      </h2>

      <ExerciseChoice
        v-if="exercise.kind === 'choice'"
        v-model="option"
        :options="exercise.options"
        :tone="tone"
        :locked="locked"
        :shown="shownOption"
        @update:model-value="edited"
      />
      <ExerciseGap
        v-else-if="exercise.kind === 'gap'"
        v-model="text"
        :text="exercise.text"
        :tone="tone"
        :locked="locked"
        @update:model-value="edited"
      />
      <ExerciseOrder
        v-else-if="exercise.kind === 'order'"
        v-model="placed"
        :tiles="exercise.tiles"
        :tone="tone"
        :locked="locked"
        @update:model-value="edited"
      />
      <ExerciseTranslate
        v-else-if="exercise.kind === 'translate'"
        v-model="text"
        :text="exercise.text"
        :exact="exercise.exact"
        :tone="tone"
        :locked="locked"
        @update:model-value="edited"
      />
      <ExerciseFix
        v-else
        v-model:word="word"
        v-model:answer="text"
        :text="exercise.text"
        :tone="tone"
        :locked="locked"
        @update:word="edited"
        @update:answer="edited"
      />
    </div>

    <div aria-live="polite">
      <div v-if="right" class="feedback feedback--right">
        <strong class="feedback__title">{{ t('exercise.right') }}</strong>
        <span>{{ exercise.explanation }}</span>
      </div>
      <div v-else-if="shown" class="feedback feedback--shown">
        <strong class="feedback__title">
          {{ t('exercise.shown') }} <span lang="en">{{ shownAnswer(exercise) }}</span>
        </strong>
        <span v-if="why">{{ why }}</span>
        <span>{{ exercise.explanation }}</span>
        <span class="feedback__task">{{ t(`exercise.shownTask.${exercise.kind}`) }}</span>
      </div>
      <div v-else-if="verdict" class="feedback feedback--wrong">
        <strong class="feedback__title">{{ t('exercise.wrong') }}</strong>
        <span>{{ why }}</span>
        <span class="feedback__task">{{ t('exercise.retry') }}</span>
      </div>
    </div>

    <div class="exercise__actions">
      <span class="exercise__attempt">
        {{ attempts > 0 && !right ? t('exercise.attempt', { n: attempts + 1 }) : '' }}
      </span>
      <BaseButton v-if="right" ref="nextButton" variant="success" @click="emit('next')">
        {{ t('exercise.next') }}
      </BaseButton>
      <BaseButton v-else type="submit" :disabled="answer === null || verdict !== null">
        {{ t('exercise.check') }}
      </BaseButton>
    </div>
  </form>
</template>

<style scoped>
.exercise {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.exercise__card {
  padding: var(--space-6);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.exercise__prompt {
  margin: 0;
  outline: none;
  font-size: var(--font-size-xl);
  font-weight: 600;
  line-height: 1.4;
}

.feedback {
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  line-height: 1.55;
}

.feedback__title {
  font-size: var(--font-size-lg);
}

.feedback__task {
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.feedback--right {
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.feedback--wrong {
  background: var(--color-danger-soft);
  color: var(--color-danger-text);
}

.feedback--shown {
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
}

.exercise__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.exercise__attempt {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

@media (max-width: 40rem) {
  .exercise__card {
    padding: var(--space-5);
  }
}
</style>
