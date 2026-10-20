<script setup lang="ts">
import { useId } from 'vue'

/**
 * Поле формы: подпись, подсказка и ошибка связаны с полем (aria-describedby), чтобы экранный
 * диктор прочитал их вместе с полем.
 */
defineOptions({ inheritAttrs: false })

const { label, hint, error } = defineProps<{
  label: string
  hint?: string
  error?: string
}>()

const value = defineModel<string>({ required: true })

const id = useId()
const hintId = `${id}-hint`
const errorId = `${id}-error`
</script>

<template>
  <div class="field">
    <label class="field__label" :for="id">{{ label }}</label>
    <input
      :id="id"
      v-model="value"
      class="field__input"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="[hint ? hintId : '', error ? errorId : ''].join(' ').trim() || undefined"
      v-bind="$attrs"
    />
    <p v-if="hint" :id="hintId" class="field__hint">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="field__error">{{ error }}</p>
  </div>
</template>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.field__label {
  font-weight: 600;
}

.field__input {
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
}

.field__input:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
}

.field__input[aria-invalid='true'] {
  border-color: var(--color-danger);
}

.field__hint,
.field__error {
  margin: 0;
  font-size: var(--font-size-sm);
}

.field__hint {
  color: var(--color-text-muted);
}

.field__error {
  color: var(--color-danger-text);
}
</style>
