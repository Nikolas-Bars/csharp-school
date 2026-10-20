<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router'

import ThemeToggle from '@/components/layout/ThemeToggle.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { t } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import { MAIN_COURSE } from '@/utils/course'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  await router.push({ name: 'home' })
}
</script>

<template>
  <header class="header">
    <RouterLink class="header__logo" to="/" :aria-label="t('brand.home')">
      <span class="header__mark" aria-hidden="true">{{ t('brand.mark') }}</span>
      <span class="header__name" aria-hidden="true">{{ t('brand.name') }}</span>
    </RouterLink>
    <nav class="header__nav" :aria-label="t('nav.label')">
      <RouterLink class="header__link" :to="{ name: 'course', params: { course: MAIN_COURSE } }">
        {{ t('nav.course') }}
      </RouterLink>
      <template v-if="auth.isLoggedIn">
        <span class="header__account">{{
          t('nav.account', { nickname: auth.user?.nickname ?? '' })
        }}</span>
        <BaseButton variant="ghost" @click="logout">{{ t('nav.logout') }}</BaseButton>
      </template>
      <!-- Пока сохранённый вход проверяется, кнопок нет: иначе «Войти» мигнёт у вошедшего -->
      <template v-else-if="auth.status === 'guest'">
        <RouterLink class="header__link" :to="{ name: 'login' }">{{ t('nav.login') }}</RouterLink>
        <RouterLink class="header__cta" :to="{ name: 'register' }">{{
          t('nav.register')
        }}</RouterLink>
      </template>
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
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
}

.header__account {
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}

/* Ссылки шапки по макету: текстовая «Войти» и тёмная кнопка */
.header__link,
.header__cta {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  font-weight: 500;
  text-decoration: none;
}

.header__link {
  color: var(--color-text);
}

.header__link:hover {
  background: var(--color-surface-muted);
}

.header__cta {
  padding-inline: var(--space-5);
  background: var(--color-inverse);
  color: var(--color-inverse-text);
  font-weight: 600;
}

.header__link:focus-visible,
.header__cta:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* Телефон: шапка не должна съедать экран урока — без надписи «Ты вошёл как…» и с меньшими отступами */
@media (max-width: 40rem) {
  .header {
    padding: var(--space-3) var(--space-4);
    gap: var(--space-2);
  }

  .header__account {
    display: none;
  }
}
</style>
