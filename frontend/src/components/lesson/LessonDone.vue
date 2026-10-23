<script lang="ts">
import type { RouteLocationRaw } from 'vue-router'

import type { ModuleResultTarget } from '@/components/lesson/ModuleResult.vue'

/** Итог модуля — свойства ModuleResult, кроме карты */
export interface LessonModuleResult {
  module: string
  left: number
  courseDone: boolean
  next: ModuleResultTarget | null
  resume: ModuleResultTarget | null
  tasks: RouteLocationRaw | null
}
</script>

<script setup lang="ts">
import { RouterLink } from 'vue-router'

import ModuleResult from '@/components/lesson/ModuleResult.vue'
import { t } from '@/i18n'

/**
 * Что дальше после проверки знаний урока (вопросы C# или упражнения английского): у контрольной —
 * итог модуля, у обычного урока — к первому непройденному шагу (задаче или практике) или на карту.
 */
const {
  result = null,
  unsolved = null,
  map,
} = defineProps<{
  /** Урок — контрольная модуля: итог модуля (ModuleResult) */
  result?: LessonModuleResult | null
  /** Первый непройденный шаг урока: подпись ссылки и адрес */
  unsolved?: { label: string; to: RouteLocationRaw } | null
  map: RouteLocationRaw
}>()
</script>

<template>
  <ModuleResult v-if="result" v-bind="result" :map="map" />
  <div v-else class="done">
    <RouterLink v-if="unsolved" class="done__link done__link--primary" :to="unsolved.to">
      {{ unsolved.label }}
    </RouterLink>
    <RouterLink
      class="done__link"
      :class="unsolved ? 'done__link--secondary' : 'done__link--map'"
      :to="map"
      >{{ t('quiz.toMap') }}</RouterLink
    >
  </div>
</template>

<style scoped>
.done {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.done__link {
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-5);
  border-radius: var(--radius);
  display: inline-flex;
  align-items: center;
  font-weight: 600;
  text-decoration: none;
}

.done__link:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* Урок пройден — на карту, зелёным */
.done__link--map {
  background: var(--color-success);
  color: var(--color-on-accent);
}

.done__link--primary {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.done__link--secondary {
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
}
</style>
