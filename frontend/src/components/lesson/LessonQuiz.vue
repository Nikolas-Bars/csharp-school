<script setup lang="ts">
import { computed, ref } from 'vue'

import BaseButton from '@/components/ui/BaseButton.vue'
import { t } from '@/i18n'
import type { QuizQuestion } from '@/types/api'

/**
 * Проверка знаний в конце урока: ответы и объяснения приходят с уроком, проверка — сразу в
 * браузере. Ошибся — видишь объяснение и можешь поменять ответ.
 */
const { questions, unsolved = 0 } = defineProps<{
  questions: QuizQuestion[]
  /** Сколько задач урока ещё не решено: тогда урок не пройден, хоть ответы и верные */
  unsolved?: number
}>()
const emit = defineEmits<{ done: [] }>()

const answers = ref<(number | null)[]>(questions.map(() => null))
const checked = ref(false)
const incomplete = ref(false)

const allCorrect = computed(() =>
  questions.every((question, index) => answers.value[index] === question.answer),
)

function check() {
  incomplete.value = answers.value.some((answer) => answer === null)
  if (incomplete.value) {
    return
  }
  checked.value = true
  if (allCorrect.value) {
    emit('done')
  }
}

function choose(question: number, option: number) {
  answers.value[question] = option
  checked.value = false
}
</script>

<template>
  <section class="quiz">
    <h2 class="quiz__title">{{ t('quiz.title') }}</h2>
    <p class="quiz__lead">{{ t('quiz.lead') }}</p>

    <fieldset v-for="(question, index) in questions" :key="index" class="quiz__question">
      <legend class="quiz__legend">
        <span class="quiz__number">{{ t('quiz.question', { n: index + 1 }) }}</span>
        {{ question.question }}
      </legend>
      <label
        v-for="(option, optionIndex) in question.options"
        :key="optionIndex"
        class="quiz__option"
      >
        <input
          type="radio"
          :name="`question-${index}`"
          :checked="answers[index] === optionIndex"
          @change="choose(index, optionIndex)"
        />
        {{ option }}
      </label>
      <p
        v-if="checked"
        :class="answers[index] === question.answer ? 'quiz__correct' : 'quiz__wrong'"
        role="status"
      >
        <strong>{{
          answers[index] === question.answer ? t('quiz.correct') : t('quiz.wrong')
        }}</strong>
        {{ question.explanation }}
      </p>
    </fieldset>

    <p v-if="incomplete" class="quiz__wrong" role="alert">{{ t('quiz.answerAll') }}</p>
    <p v-if="checked && allCorrect" class="quiz__correct quiz__done" role="status">
      <strong>{{
        unsolved > 0 ? t('quiz.doneTasksLeft', { n: unsolved }) : t('quiz.done')
      }}</strong>
    </p>
    <BaseButton v-else @click="check">{{ t('quiz.check') }}</BaseButton>
    <slot v-if="checked && allCorrect" name="done" />
  </section>
</template>

<style scoped>
.quiz {
  max-width: 44rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.quiz__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.quiz__lead {
  margin: 0;
  color: var(--color-text-muted);
}

.quiz__question {
  margin: 0;
  padding: var(--space-4) var(--space-5);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.quiz__legend {
  padding: 0;
  margin-bottom: var(--space-2);
  font-weight: 600;
  font-size: var(--font-size-lg);
}

.quiz__number {
  display: block;
  font-size: var(--font-size-sm);
  color: var(--color-accent-text);
}

.quiz__option {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-height: 2.75rem;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius);
  cursor: pointer;
}

.quiz__option:hover {
  background: var(--color-surface-muted);
}

.quiz__correct,
.quiz__wrong {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
}

.quiz__correct {
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.quiz__wrong {
  background: var(--color-danger-soft);
  color: var(--color-danger-text);
}
</style>
