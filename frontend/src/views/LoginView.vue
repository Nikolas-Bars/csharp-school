<script setup lang="ts">
import { computed, reactive } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import AuthCard from '@/components/auth/AuthCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { t } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import { safeRedirect } from '@/utils/safeRedirect'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { submitting, fieldErrors, formError, submit } = useFormSubmit()

const form = reactive({ email: '', password: '' })

// Сюда возвращает страница нового пароля: ?reset=done
const notice = computed(() =>
  route.query.reset === 'done' ? t('auth.login.passwordChanged') : undefined,
)

async function login() {
  if (await submit(() => auth.login(form))) {
    await router.push(safeRedirect(route.query.redirect))
  }
}
</script>

<template>
  <AuthCard
    :title="t('auth.login.title')"
    :lead="t('auth.login.lead')"
    :error="formError"
    :notice="notice"
  >
    <form class="auth-form" novalidate @submit.prevent="login">
      <BaseField
        v-model="form.email"
        :label="t('auth.fields.email')"
        :error="fieldErrors.email"
        type="email"
        autocomplete="email"
        required
      />
      <BaseField
        v-model="form.password"
        :label="t('auth.fields.password')"
        :error="fieldErrors.password"
        type="password"
        autocomplete="current-password"
        required
      />
      <!-- Набранная почта переходит на страницу «Забыли пароль?», чтобы не вводить её снова -->
      <RouterLink
        class="auth-form__forgot"
        :to="{ name: 'forgot-password', query: form.email ? { email: form.email } : {} }"
        >{{ t('auth.login.forgot') }}</RouterLink
      >
      <BaseButton type="submit" :loading="submitting">
        {{ submitting ? t('auth.login.submitting') : t('auth.login.submit') }}
      </BaseButton>
    </form>
    <template #footer>
      {{ t('auth.login.noAccount') }}
      <RouterLink :to="{ name: 'register', query: route.query }">{{
        t('auth.login.toRegister')
      }}</RouterLink>
    </template>
  </AuthCard>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

/* Ссылка под полем пароля: зона касания не меньше 44 px */
.auth-form__forgot {
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
}
</style>
