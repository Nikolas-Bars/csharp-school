<script setup lang="ts">
/**
 * Кнопка по макету: primary — главное действие, success — «Дальше» после верного ответа, dark —
 * акцент в шапке, ghost — второстепенное. disabled — пока нечего отправлять, loading — уже отправляем
 */
const {
  type = 'button',
  variant = 'primary',
  loading = false,
  disabled = false,
} = defineProps<{
  type?: 'button' | 'submit'
  variant?: 'primary' | 'success' | 'dark' | 'ghost'
  loading?: boolean
  disabled?: boolean
}>()
</script>

<template>
  <button
    :class="['button', `button--${variant}`]"
    :type="type"
    :disabled="loading || disabled"
    :aria-busy="loading"
  >
    <slot />
  </button>
</template>

<style scoped>
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-5);
  border: 1px solid transparent;
  border-radius: var(--radius);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.button:disabled {
  opacity: 0.7;
  cursor: progress;
}

.button:disabled:not([aria-busy='true']) {
  cursor: not-allowed;
}

.button:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.button--primary {
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.button--primary:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.button--success {
  background: var(--color-success);
  color: var(--color-on-accent);
}

.button--success:hover:not(:disabled) {
  background: var(--color-success-hover);
}

.button--dark {
  background: var(--color-inverse);
  color: var(--color-inverse-text);
}

.button--ghost {
  background: transparent;
  border-color: var(--color-border);
  color: var(--color-text);
}

.button--ghost:hover:not(:disabled) {
  background: var(--color-surface-muted);
}
</style>
