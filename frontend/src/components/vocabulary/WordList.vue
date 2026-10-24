<script setup lang="ts">
import { computed, ref } from 'vue'

import BaseSpeakButton from '@/components/ui/BaseSpeakButton.vue'
import { t } from '@/i18n'
import type { PartOfSpeech } from '@/types/api'
import { daysUntil, LAST_BOX } from '@/utils/repetition'

/** Слово в списке словаря: карточка, где появилось и когда повторить */
export interface WordRow {
  id: string
  word: string
  ipa: string
  pos: PartOfSpeech
  translation: string
  /** «Модуль 2 · Урок 1» */
  lesson: string
  /** Номер модуля для фильтра */
  module: number
  box: number
  dueAt: string
}

/**
 * Список «Мой словарь» по макету: поиск по слову и переводу, фильтр «все / сегодня / модуль», у
 * каждого слова — звук и когда повторить.
 */
const { rows, now } = defineProps<{ rows: WordRow[]; now: Date }>()

const query = ref('')
/** all, today или номер модуля */
const filter = ref<'all' | 'today' | number>('all')

const isToday = (row: WordRow) => daysUntil(row.dueAt, now) === 0
const modules = computed(() => [...new Set(rows.map((row) => row.module))].sort((a, b) => a - b))
const todayCount = computed(() => rows.filter(isToday).length)

const filters = computed(() => [
  { value: 'all' as const, label: t('words.dictionary.all', { n: rows.length }) },
  { value: 'today' as const, label: t('words.dictionary.today', { n: todayCount.value }) },
  ...modules.value.map((module) => ({
    value: module,
    label: t('words.dictionary.module', { n: module }),
  })),
])

const shown = computed(() => {
  const needle = query.value.trim().toLowerCase()
  return rows.filter(
    (row) =>
      (filter.value === 'all' ||
        (filter.value === 'today' ? isToday(row) : row.module === filter.value)) &&
      (needle === '' ||
        row.word.toLowerCase().includes(needle) ||
        row.translation.toLowerCase().includes(needle)),
  )
})

/** «Повторить сегодня», «Через 3 дня», «Выучено · через 30 дней» */
function due(row: WordRow): { text: string; tone: 'today' | 'learned' | 'later' } {
  const days = daysUntil(row.dueAt, now)
  if (days === 0) {
    return { text: t('words.dictionary.dueToday'), tone: 'today' }
  }
  const learned = row.box >= LAST_BOX
  // «Завтра» — отдельно: по русскому правилу 21 тоже «один», а «Завтра» для 21 дня — неправда
  const text =
    days === 1
      ? t(learned ? 'words.dictionary.learnedTomorrow' : 'words.dictionary.tomorrow')
      : t(learned ? 'words.dictionary.learned' : 'words.dictionary.dueIn', days)
  return { text, tone: learned ? 'learned' : 'later' }
}
</script>

<template>
  <div class="list">
    <div class="list__tools">
      <label class="list__search">
        <span class="list__visually-hidden">{{ t('words.dictionary.searchLabel') }}</span>
        <input
          v-model="query"
          class="list__input"
          type="search"
          :placeholder="t('words.dictionary.search')"
        />
      </label>
      <div class="list__filters" role="group" :aria-label="t('words.dictionary.filter')">
        <button
          v-for="item in filters"
          :key="item.value"
          type="button"
          class="list__filter"
          :class="{ 'list__filter--on': filter === item.value }"
          :aria-pressed="filter === item.value"
          @click="filter = item.value"
        >
          {{ item.label }}
        </button>
      </div>
    </div>

    <ul v-if="shown.length > 0" class="list__rows">
      <li v-for="row in shown" :key="row.id" class="list__row">
        <BaseSpeakButton :text="row.word" />
        <div class="list__word">
          <strong lang="en">{{ row.word }}</strong>
          <span class="list__ipa">{{ row.ipa }}</span>
        </div>
        <div class="list__meaning">
          <span>{{ row.translation }}</span>
          <span class="list__meta">{{ t(`words.pos.${row.pos}`) }} · {{ row.lesson }}</span>
        </div>
        <span class="list__due" :class="`list__due--${due(row).tone}`">{{ due(row).text }}</span>
      </li>
    </ul>
    <p v-else class="list__empty">{{ t('words.dictionary.notFound') }}</p>
  </div>
</template>

<style scoped>
.list {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.list__tools {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-3);
}

.list__search {
  flex: 1 1 17.5rem;
  min-width: 0;
}

.list__visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

.list__input {
  width: 100%;
  box-sizing: border-box;
  min-height: 3rem;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
}

.list__input:focus-visible,
.list__filter:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.list__filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.list__filter {
  min-height: 2.75rem;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-pill);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.list__filter--on {
  border-color: var(--color-inverse);
  background: var(--color-inverse);
  color: var(--color-inverse-text);
}

.list__rows {
  margin: 0;
  padding: 0;
  list-style: none;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  overflow: hidden;
}

.list__row {
  padding: var(--space-3) var(--space-5);
  border-bottom: 1px solid var(--color-surface-muted);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-3) var(--space-5);
}

.list__row:last-child {
  border-bottom: none;
}

.list__word {
  flex: 1 1 12rem;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  font-size: var(--font-size-lg);
}

.list__ipa {
  font-family: var(--font-mono);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.list__meaning {
  flex: 2 1 15rem;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.list__meta {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.list__due {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-pill);
  font-size: var(--font-size-sm);
  font-weight: 600;
  white-space: nowrap;
}

.list__due--today {
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
}

.list__due--learned {
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.list__due--later {
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
}

.list__empty {
  margin: 0;
  padding: var(--space-6) var(--space-5);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text-muted);
}

@media (max-width: 40rem) {
  .list__row {
    padding: var(--space-3) var(--space-4);
  }
}
</style>
