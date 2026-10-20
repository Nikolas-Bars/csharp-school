<script setup lang="ts">
import { reactive } from 'vue'
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

const form = reactive({ nickname: '', email: '', password: '' })

async function register() {
  if (await submit(() => auth.register(form))) {
    await router.push(safeRedirect(route.query.redirect))
  }
}
</script>

<template>
  <AuthCard :title="t('auth.register.title')" :lead="t('auth.register.lead')" :error="formError">
    <form class="auth-form" novalidate @submit.prevent="register">
      <BaseField
        v-model="form.nickname"
        :label="t('auth.fields.nickname')"
        :hint="t('auth.fields.nicknameHint')"
        :error="fieldErrors.nickname"
        autocomplete="nickname"
        maxlength="32"
        required
      />
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
        :hint="t('auth.fields.passwordHint')"
        :error="fieldErrors.password"
        type="password"
        autocomplete="new-password"
        required
      />
      <BaseButton type="submit" :loading="submitting">
        {{ submitting ? t('auth.register.submitting') : t('auth.register.submit') }}
      </BaseButton>
    </form>
    <template #footer>
      {{ t('auth.register.hasAccount') }}
      <RouterLink :to="{ name: 'login', query: route.query }">{{
        t('auth.register.toLogin')
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
</style>
