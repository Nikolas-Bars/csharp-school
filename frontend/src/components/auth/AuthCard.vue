<script setup lang="ts">
/**
 * Оболочка форм входа, регистрации и смены пароля: заголовок, пояснение, форма и ссылка на
 * соседнюю форму. notice — хорошая новость («пароль изменён»), error — что пошло не так.
 */
const { title, lead, error, notice } = defineProps<{
  title: string
  lead: string
  error?: string
  notice?: string
}>()
</script>

<template>
  <main class="auth">
    <section class="auth__card" :aria-labelledby="'auth-title'">
      <h1 id="auth-title" class="auth__title">{{ title }}</h1>
      <p class="auth__lead">{{ lead }}</p>
      <p v-if="notice" class="auth__notice" role="status">{{ notice }}</p>
      <p v-if="error" class="auth__error" role="alert">{{ error }}</p>
      <slot />
      <p class="auth__footer"><slot name="footer" /></p>
    </section>
  </main>
</template>

<style scoped>
.auth {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-6) var(--space-4) var(--space-7);
  display: flex;
  justify-content: center;
}

.auth__card {
  width: 100%;
  max-width: 28rem;
  padding: var(--space-6);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.auth__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-xxl);
}

.auth__lead,
.auth__footer {
  margin: 0;
  color: var(--color-text-muted);
}

.auth__notice {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-sm);
  background: var(--color-success-soft);
  color: var(--color-success-text);
}

.auth__error {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-sm);
  background: var(--color-danger-soft);
  color: var(--color-danger-text);
}
</style>
