<script setup lang="ts">
import { t } from '@/i18n'
</script>

<template>
  <main class="home">
    <span class="home__badge">{{ t('home.badge') }}</span>
    <h1 class="home__title">{{ t('home.title') }}</h1>
    <p class="home__lead">{{ t('home.lead') }}</p>
  </main>
</template>

<style scoped>
.home {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-7) var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.home__badge {
  align-self: flex-start;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-pill);
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
  font-weight: 600;
  font-size: var(--font-size-sm);
}

.home__title {
  margin: 0;
  max-width: 45rem;
  font-family: var(--font-display);
  font-size: clamp(var(--font-size-xxl), 6vw, var(--font-size-display));
  line-height: 1.1;
}

.home__lead {
  margin: 0;
  max-width: 35rem;
  font-size: var(--font-size-lg);
  color: var(--color-text-muted);
}
</style>
