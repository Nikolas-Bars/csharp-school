<script setup lang="ts">
import type { Inline } from '@/utils/markdown'

/** Строка текста урока: обычный текст, **жирный**, `код`, ==слово-маркер== */
const { content } = defineProps<{ content: Inline[] }>()
</script>

<template>
  <template v-for="(part, index) in content" :key="index">
    <strong v-if="part.kind === 'strong'">{{ part.text }}</strong>
    <code v-else-if="part.kind === 'code'" class="inline__code">{{ part.text }}</code>
    <mark v-else-if="part.kind === 'mark'" class="inline__mark" lang="en">{{ part.text }}</mark>
    <template v-else>{{ part.text }}</template>
  </template>
</template>

<style scoped>
.inline__code {
  padding: 0.1em 0.35em;
  border-radius: var(--radius-sm);
  background: var(--color-surface-muted);
  font-family: var(--font-mono);
  font-size: 0.9em;
}

.inline__mark {
  padding: 0.05em 0.4em;
  border-radius: var(--radius-sm);
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
  font-weight: 600;
}
</style>
