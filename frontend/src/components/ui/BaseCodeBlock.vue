<script setup lang="ts">
import { computed } from 'vue'

import { tokenizeCSharp } from '@/utils/csharpTokens'

/** Блок кода в теории: C# раскрашен так же, как в редакторе; другой язык — как есть */
const { code, language = 'csharp' } = defineProps<{ code: string; language?: string }>()

const tokens = computed(() =>
  language === 'csharp' ? tokenizeCSharp(code) : [{ kind: 'plain' as const, text: code }],
)
</script>

<template>
  <pre
    class="code"
    :class="{ 'code--output': language !== 'csharp' }"
  ><code><span v-for="(token, index) in tokens" :key="index" :class="`code__${token.kind}`">{{ token.text }}</span></code></pre>
</template>

<style scoped>
.code {
  margin: 0;
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius);
  background: var(--color-code-bg);
  color: var(--color-code-text);
  font-family: var(--font-mono);
  font-size: var(--font-size-sm);
  line-height: 1.7;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

/* Вывод программы и прочий текст — без раскраски, чуть светлее */
.code--output {
  background: var(--color-code-panel);
}

.code__keyword {
  color: var(--color-code-keyword);
}

.code__type {
  color: var(--color-code-type);
}

.code__string {
  color: var(--color-code-string);
}

.code__number {
  color: var(--color-code-number);
}

.code__comment {
  color: var(--color-code-comment);
}
</style>
