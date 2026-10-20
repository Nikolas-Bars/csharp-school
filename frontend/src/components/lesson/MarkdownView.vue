<script setup lang="ts">
import { computed } from 'vue'

import CodeBlock from '@/components/lesson/CodeBlock.vue'
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
        <template v-for="(part, partIndex) in block.content" :key="partIndex">
          <code v-if="part.kind === 'code'" class="markdown__code">{{ part.text }}</code>
          <template v-else>{{ part.text }}</template>
        </template>
      </component>
      <p v-else-if="block.kind === 'paragraph'" class="markdown__paragraph">
        <template v-for="(part, partIndex) in block.content" :key="partIndex">
          <strong v-if="part.kind === 'strong'">{{ part.text }}</strong>
          <code v-else-if="part.kind === 'code'" class="markdown__code">{{ part.text }}</code>
          <template v-else>{{ part.text }}</template>
        </template>
      </p>
      <ul v-else-if="block.kind === 'list'" class="markdown__list">
        <li v-for="(item, itemIndex) in block.items" :key="itemIndex">
          <template v-for="(part, partIndex) in item" :key="partIndex">
            <strong v-if="part.kind === 'strong'">{{ part.text }}</strong>
            <code v-else-if="part.kind === 'code'" class="markdown__code">{{ part.text }}</code>
            <template v-else>{{ part.text }}</template>
          </template>
        </li>
      </ul>
      <CodeBlock v-else :code="block.code" :language="block.language || 'text'" />
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

.markdown__code {
  padding: 0.1em 0.35em;
  border-radius: var(--radius-sm);
  background: var(--color-surface-muted);
  font-family: var(--font-mono);
  font-size: 0.9em;
}
</style>
