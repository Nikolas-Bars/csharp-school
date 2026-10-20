<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import AuthCard from '@/components/auth/AuthCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { t } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import { queryText } from '@/utils/queryText'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { submitting, fieldErrors, formError, submit } = useFormSubmit()

// Ссылка из письма: /reset-password?token=…&email=…
const token = queryText(route.query.token)
const email = ref(queryText(route.query.email))
const password = ref('')
const brokenLink = token === '' || email.value === ''

// Ошибки токена и почты относятся к ссылке, а не к полю на экране — показываем над формой
const error = computed(
  () =>
    (brokenLink ? t('auth.reset.brokenLink') : '') ||
    formError.value ||
    fieldErrors.value.token ||
    fieldErrors.value.email ||
    '',
)

async function save() {
  const form = { token, email: email.value, password: password.value }
  if (await submit(() => auth.resetPassword(form))) {
    await router.push({ name: 'login', query: { reset: 'done' } })
  }
}
</script>

<template>
  <AuthCard :title="t('auth.reset.title')" :lead="t('auth.reset.lead')" :error="error">
    <form v-if="!brokenLink" class="auth-form" novalidate @submit.prevent="save">
      <!-- Почта — только для чтения: по ней менеджер паролей поймёт, для какого аккаунта пароль -->
      <BaseField
        v-model="email"
        :label="t('auth.fields.email')"
        type="email"
        autocomplete="username"
        readonly
      />
      <BaseField
        v-model="password"
        :label="t('auth.fields.newPassword')"
        :hint="t('auth.fields.passwordHint')"
        :error="fieldErrors.password"
        type="password"
        autocomplete="new-password"
        required
      />
      <BaseButton type="submit" :loading="submitting">
        {{ submitting ? t('auth.reset.submitting') : t('auth.reset.submit') }}
      </BaseButton>
    </form>
    <template #footer>
      {{ t('auth.reset.linkTrouble') }}
      <RouterLink :to="{ name: 'forgot-password', query: email ? { email } : {} }">{{
        t('auth.reset.requestNew')
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
