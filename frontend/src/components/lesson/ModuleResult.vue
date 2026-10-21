<script setup lang="ts">
import { computed, onMounted, useTemplateRef } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'

import BaseIcon from '@/components/ui/BaseIcon.vue'
import { t } from '@/i18n'

/** Куда перейти: название урока или модуля и адрес */
export interface ModuleResultTarget {
  title: string
  to: RouteLocationRaw
}

/**
 * Итог модуля после вопросов контрольной. Модуль пройден, когда пройдены все его уроки вместе
 * с контрольной: тогда — поздравление и следующий модуль; курс пройден целиком — карта; в курсе
 * ещё есть непройденное — первый непройденный урок. Модуль не пройден — сколько уроков осталось
 * и с чего продолжить. Появляется — фокус на заголовок: диктор читает итог, а кнопка
 * «Проверить ответы», где был фокус, уже исчезла.
 */
const {
  module,
  left,
  courseDone = false,
  next = null,
  resume = null,
  tasks = null,
  map,
} = defineProps<{
  /** Название модуля */
  module: string
  /** Сколько уроков модуля ещё не пройдено (контрольная — тоже урок) */
  left: number
  /** Пройдены все модули курса */
  courseDone?: boolean
  /** Модуль пройден — следующий модуль, с первого непройденного в нём урока */
  next?: ModuleResultTarget | null
  /** Первый непройденный урок: этого модуля, а если он пройден — курса */
  resume?: ModuleResultTarget | null
  /** Не решены задачи этой контрольной — её первая нерешённая задача */
  tasks?: RouteLocationRaw | null
  map: RouteLocationRaw
}>()

const complete = computed(() => left === 0)

/** Ссылки по порядку: первая — главная; если их нет, главная — карта */
const links = computed<ModuleResultTarget[]>(() => {
  if (complete.value) {
    if (courseDone) {
      return []
    }
    if (next) {
      return [{ title: t('moduleResult.next', { title: next.title }), to: next.to }]
    }
    return resume
      ? [{ title: t('moduleResult.continue', { title: resume.title }), to: resume.to }]
      : []
  }
  return [
    ...(resume
      ? [{ title: t('moduleResult.continue', { title: resume.title }), to: resume.to }]
      : []),
    ...(tasks ? [{ title: t('moduleResult.solveTasks'), to: tasks }] : []),
  ]
})

const heading = useTemplateRef<HTMLElement>('heading')
onMounted(() => heading.value?.focus())
</script>

<template>
  <section class="result" :class="{ 'result--done': complete }" aria-labelledby="module-result">
    <div class="result__text">
      <h2 id="module-result" ref="heading" class="result__title" tabindex="-1">
        <BaseIcon v-if="complete" name="check" :size="28" />
        {{ complete ? t('moduleResult.title') : t('moduleResult.almostTitle') }}
      </h2>
      <p class="result__lead">
        {{
          complete ? t('moduleResult.lead', { module }) : t('moduleResult.almostLead', { n: left })
        }}
      </p>
      <p v-if="complete && courseDone" class="result__lead">{{ t('moduleResult.courseDone') }}</p>
      <p v-else-if="complete && !next && resume" class="result__lead">
        {{ t('moduleResult.courseLeft') }}
      </p>
    </div>
    <div class="result__actions">
      <RouterLink
        v-for="(link, linkIndex) in links"
        :key="linkIndex"
        class="result__link"
        :class="linkIndex === 0 ? 'result__link--primary' : 'result__link--secondary'"
        :to="link.to"
      >
        {{ link.title }}
      </RouterLink>
      <RouterLink
        class="result__link"
        :class="links.length > 0 ? 'result__link--secondary' : 'result__link--primary'"
        :to="map"
      >
        {{ t('moduleResult.toMap') }}
      </RouterLink>
    </div>
  </section>
</template>

<style scoped>
.result {
  padding: var(--space-5);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.result--done {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.result__text {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.result__title {
  margin: 0;
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-family: var(--font-display);
  font-size: var(--font-size-xl);
}

.result__title:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.result--done .result__title {
  color: var(--color-success-text);
}

.result__lead {
  margin: 0;
  color: var(--color-text);
}

.result__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.result__link {
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-5);
  border-radius: var(--radius);
  display: inline-flex;
  align-items: center;
  font-weight: 600;
  text-decoration: none;
}

.result__link--primary {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.result__link--primary:hover {
  background: var(--color-primary-hover);
}

.result__link--secondary {
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
}

.result__link--secondary:hover {
  border-color: var(--color-primary);
}

.result__link:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
</style>
