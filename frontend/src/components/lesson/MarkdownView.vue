<script setup lang="ts">
import { computed } from 'vue'

import MarkdownInline from '@/components/lesson/MarkdownInline.vue'
import BaseCodeBlock from '@/components/ui/BaseCodeBlock.vue'
import BaseSpeakButton from '@/components/ui/BaseSpeakButton.vue'
import { t } from '@/i18n'
import { parseMarkdown } from '@/utils/markdown'

/** Текст урока: элементы строятся из разобранной структуры, без v-html */
const { source } = defineProps<{ source: string }>()

const blocks = computed(() => parseMarkdown(source))
</script>

<template>
  <div class="markdown">
    <template v-for="(block, index) in blocks" :key="index">
      <component
        :is="`h${block.level + 1}`"
        v-if="block.kind === 'heading'"
        :class="['markdown__heading', `markdown__heading--${block.level}`]"
      >
        <MarkdownInline :content="block.content" />
      </component>
      <p v-else-if="block.kind === 'paragraph'" class="markdown__paragraph">
        <MarkdownInline :content="block.content" />
      </p>
      <component
        :is="block.ordered ? 'ol' : 'ul'"
        v-else-if="block.kind === 'list'"
        class="markdown__list"
      >
        <li v-for="(item, itemIndex) in block.items" :key="itemIndex">
          <MarkdownInline :content="item" />
        </li>
      </component>
      <div v-else-if="block.kind === 'table'" class="markdown__table-wrap">
        <table class="markdown__table">
          <thead>
            <tr>
              <th v-for="(cell, cellIndex) in block.head" :key="cellIndex" scope="col">
                <MarkdownInline :content="cell" />
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, rowIndex) in block.rows" :key="rowIndex">
              <component
                :is="cellIndex === 0 ? 'th' : 'td'"
                v-for="(cell, cellIndex) in row"
                :key="cellIndex"
                :scope="cellIndex === 0 ? 'row' : undefined"
              >
                <MarkdownInline :content="cell" />
              </component>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="block.kind === 'note'" class="markdown__note">
        <MarkdownInline :content="block.content" />
      </p>
      <ul v-else-if="block.kind === 'examples'" class="markdown__examples">
        <li v-for="(example, exampleIndex) in block.items" :key="exampleIndex" class="example">
          <BaseSpeakButton :text="example.en" />
          <span class="example__text">
            <span class="example__en" lang="en">{{ example.en }}</span>
            <span class="example__translation">{{ example.translation }}</span>
          </span>
        </li>
      </ul>
      <div v-else-if="block.kind === 'mistakes'" class="markdown__mistakes">
        <div v-for="(mistake, mistakeIndex) in block.items" :key="mistakeIndex" class="mistake">
          <span class="mistake__wrong" lang="en">
            <span class="visually-hidden">{{ t('lesson.mistakeWrong') }}</span>
            <span aria-hidden="true">✗ </span><s>{{ mistake.wrong }}</s>
          </span>
          <span class="mistake__right" lang="en">
            <span class="visually-hidden">{{ t('lesson.mistakeRight') }}</span>
            <span aria-hidden="true">✓ </span>{{ mistake.right }}
          </span>
          <span class="mistake__why">{{ mistake.why }}</span>
        </div>
      </div>
      <BaseCodeBlock v-else :code="block.code" :language="block.language || 'text'" />
    </template>
  </div>
</template>

<style scoped>
.markdown {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  font-size: var(--font-size-lg);
  line-height: 1.65;
}

.markdown__heading {
  margin: 0;
  font-family: var(--font-display);
  line-height: 1.25;
}

.markdown__heading--1 {
  font-size: var(--font-size-xxl);
}

.markdown__heading--2,
.markdown__heading--3 {
  font-size: var(--font-size-xl);
}

.markdown__paragraph {
  margin: 0;
}

.markdown__list {
  margin: 0;
  padding-left: var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

/* Широкая таблица на телефоне листается внутри своей рамки, а не всей страницей */
.markdown__table-wrap {
  overflow-x: auto;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
}

.markdown__table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-md);
  line-height: 1.5;
}

.markdown__table th,
.markdown__table td {
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--color-border);
  text-align: left;
  vertical-align: top;
}

.markdown__table thead th {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.markdown__table tbody tr:last-child > * {
  border-bottom: none;
}

.markdown__table :deep(strong) {
  color: var(--color-accent-text);
}

.markdown__note {
  margin: 0;
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius-lg);
  background: var(--color-warning-soft);
  color: var(--color-warning-text);
  font-size: var(--font-size-md);
}

.markdown__examples {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.example {
  padding: var(--space-4) var(--space-5);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  align-items: center;
  gap: var(--space-4);
}

.example__text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.example__en {
  font-weight: 600;
}

.example__translation {
  font-size: var(--font-size-md);
  color: var(--color-text-muted);
}

.markdown__mistakes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: var(--space-3);
}

.mistake {
  padding: var(--space-4) var(--space-5);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.mistake__wrong {
  color: var(--color-danger-text);
}

.mistake__right {
  color: var(--color-success-text);
  font-weight: 600;
}

.mistake__why {
  font-size: var(--font-size-md);
  line-height: 1.55;
  color: var(--color-text-muted);
}
</style>
