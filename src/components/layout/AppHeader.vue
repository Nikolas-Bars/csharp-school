<script setup lang="ts">
import { RouterLink } from 'vue-router'

import ThemeToggle from '@/components/layout/ThemeToggle.vue'
import { t } from '@/i18n'
</script>

<template>
  <header class="header">
    <RouterLink class="header__logo" to="/" :aria-label="t('brand.home')">
      <span class="header__mark" aria-hidden="true">{{ t('brand.mark') }}</span>
      <span class="header__name" aria-hidden="true">{{ t('brand.name') }}</span>
    </RouterLink>
    <nav class="header__nav" :aria-label="t('nav.label')">
      <ThemeToggle />
    </nav>
  </header>
</template>

<style scoped>
.header {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-5) var(--space-6);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
}

.header__logo {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-height: 2.75rem;
  color: var(--color-text);
  text-decoration: none;
}

.header__mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: var(--radius);
  background: var(--color-primary);
  color: var(--color-on-accent);
  font-family: var(--font-display);
  font-weight: 700;
}

.header__name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: var(--font-size-xl);
}

.header__nav {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}
</style>
