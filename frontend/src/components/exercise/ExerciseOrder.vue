<script setup lang="ts">
import { computed, nextTick, useTemplateRef } from 'vue'

import { t } from '@/i18n'

import type { Tone } from './tone'

/**
 * Собрать фразу из плиток: касание (или Enter/пробел) по слову снизу ставит его в конец фразы,
 * по слову во фразе — возвращает вниз. Модель — номера плиток по порядку во фразе. Нажатая плитка
 * пропадает из своего ряда — фокус переходит на соседнюю в том же ряду, а если ряд опустел — в
 * другой: клавиатура не теряет место.
 */
const { tiles, tone, locked } = defineProps<{ tiles: string[]; tone: Tone; locked: boolean }>()
const placed = defineModel<number[]>({ required: true })

const pool = computed(() =>
  tiles.flatMap((text, index) => (placed.value.includes(index) ? [] : [{ text, index }])),
)

const line = useTemplateRef<HTMLElement>('line')
const poolRow = useTemplateRef<HTMLElement>('poolRow')

/** Фокус на плитку номер at в ряду (или последнюю); ряд пуст — на последнюю в другом ряду */
async function focusNear(row: HTMLElement | null, other: HTMLElement | null, at: number) {
  await nextTick()
  const tiles = [...(row?.querySelectorAll('button') ?? [])]
  const fallback = [...(other?.querySelectorAll('button') ?? [])]
  ;(tiles[Math.min(at, tiles.length - 1)] ?? fallback[fallback.length - 1])?.focus()
}

function put(index: number) {
  if (!locked) {
    const at = pool.value.findIndex((tile) => tile.index === index)
    placed.value = [...placed.value, index]
    void focusNear(poolRow.value, line.value, at)
  }
}

function take(position: number) {
  if (!locked) {
    placed.value = placed.value.filter((_, at) => at !== position)
    void focusNear(line.value, poolRow.value, position)
  }
}
</script>

<template>
  <div class="order">
    <div
      ref="line"
      class="order__line"
      :class="`order__line--${tone}`"
      role="group"
      :aria-label="t('exercise.orderLine')"
    >
      <span v-if="placed.length === 0" class="order__empty">{{ t('exercise.orderEmpty') }}</span>
      <button
        v-for="(index, position) in placed"
        :key="`${index}-${position}`"
        type="button"
        class="order__tile order__tile--placed"
        lang="en"
        :disabled="locked"
        :aria-label="t('exercise.orderRemove', { word: tiles[index] ?? '' })"
        @click="take(position)"
      >
        {{ tiles[index] }}
      </button>
    </div>
    <div ref="poolRow" class="order__pool" role="group" :aria-label="t('exercise.orderPool')">
      <button
        v-for="tile in pool"
        :key="tile.index"
        type="button"
        class="order__tile"
        lang="en"
        :disabled="locked"
        :aria-label="t('exercise.orderAdd', { word: tile.text })"
        @click="put(tile.index)"
      >
        {{ tile.text }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.order {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.order__line {
  min-height: 4.5rem;
  padding: var(--space-3);
  border: 2px dashed var(--color-primary-border);
  border-radius: var(--radius-lg);
  background: var(--color-background);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
}

.order__line--right {
  border-color: var(--color-success);
  background: var(--color-success-soft);
}

.order__line--wrong {
  border-color: var(--color-danger);
  background: var(--color-danger-soft);
}

.order__empty {
  font-size: var(--font-size-md);
  color: var(--color-text-muted);
}

.order__pool {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.order__tile {
  min-height: 3rem;
  padding: var(--space-2) var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-text);
  box-shadow: 0 2px 0 var(--color-border);
  font: inherit;
  font-size: var(--font-size-lg);
  font-weight: 600;
  cursor: pointer;
}

.order__tile--placed {
  border-color: var(--color-primary-border);
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
  box-shadow: none;
}

.order__tile:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.order__tile:disabled {
  cursor: default;
}
</style>
