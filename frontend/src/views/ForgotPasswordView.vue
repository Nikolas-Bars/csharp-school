<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import AuthCard from '@/components/auth/AuthCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { t } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import { queryText } from '@/utils/queryText'

const auth = useAuthStore()
const route = useRoute()
const { submitting, fieldErrors, formError, submit } = useFormSubmit()

// Почта, уже набранная на странице входа, приходит в адресе
const form = reactive({ email: queryText(route.query.email) })
/** Куда ушло письмо; ответ сервера одинаковый, есть такой аккаунт или нет */
const sentTo = ref('')

async function send() {
  sentTo.value = ''
  if (await submit(() => auth.forgotPassword(form))) {
    sentTo.value = form.email.trim()
  }
}
</script>

<template>
  <AuthCard
    :title="t('auth.forgot.title')"
    :lead="t('auth.forgot.lead')"
    :error="formError"
    :notice="sentTo ? t('auth.forgot.sent', { email: sentTo }) : undefined"
  >
    <!-- Форма остаётся и после отправки: фокус не теряется, а письмо можно запросить ещё раз -->
    <form class="auth-form" novalidate @submit.prevent="send">
      <BaseField
        v-model="form.email"
        :label="t('auth.fields.email')"
        :error="fieldErrors.email"
        type="email"
        autocomplete="email"
        required
      />
      <BaseButton type="submit" :loading="submitting">
        {{ submitting ? t('auth.forgot.submitting') : t('auth.forgot.submit') }}
      </BaseButton>
    </form>
    <template #footer>
      {{ t('auth.forgot.remembered') }}
      <RouterLink :to="{ name: 'login' }">{{ t('auth.forgot.toLogin') }}</RouterLink>
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
