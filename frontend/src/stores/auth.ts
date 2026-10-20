import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { authApi } from '@/api/auth'
import { ApiError, onUnauthorized } from '@/api/http'
import { tokenStorage } from '@/api/tokenStorage'
import { isLocale, setLocale } from '@/i18n/locale'
import type { AuthSession, User } from '@/types/api'

/**
 * Вход ученика. Учиться можно и без аккаунта, поэтому «гость» — нормальное состояние, а не ошибка.
 * unknown — сохранённый токен ещё проверяется (первая загрузка страницы).
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const status = ref<'unknown' | 'guest' | 'user'>(
    tokenStorage.get() === null ? 'guest' : 'unknown',
  )

  const isLoggedIn = computed(() => user.value !== null)

  let restoring: Promise<void> | null = null

  function start(session: AuthSession) {
    tokenStorage.set(session.token)
    signIn(session.user)
  }

  function signIn(account: User) {
    user.value = account
    status.value = 'user'
    // Язык аккаунта общий для всех устройств
    if (isLocale(account.locale)) {
      setLocale(account.locale)
    }
  }

  function forget() {
    tokenStorage.clear()
    user.value = null
    status.value = 'guest'
  }

  /** Сохранённый токен → ученик. Вызывается один раз; повторные вызовы ждут первый */
  function restore(): Promise<void> {
    restoring ??= (async () => {
      if (tokenStorage.get() === null) {
        status.value = 'guest'
        return
      }
      try {
        signIn(await authApi.me())
      } catch (error) {
        // 401 — токен недействителен; нет сети — тоже гость, токен сохраняем до следующей попытки
        if (error instanceof ApiError && error.status === 401) {
          forget()
        } else {
          status.value = 'guest'
        }
      }
    })()
    return restoring
  }

  async function register(form: { nickname: string; email: string; password: string }) {
    start(await authApi.register(form))
  }

  async function login(form: { email: string; password: string }) {
    start(await authApi.login(form))
  }

  /** «Забыли пароль?» — письмо со ссылкой. Состояния не меняет: ученик пока гость */
  async function forgotPassword(form: { email: string }) {
    await authApi.forgotPassword(form)
  }

  /** Новый пароль по ссылке. Сервер закрывает все входы, поэтому дальше — обычный вход */
  async function resetPassword(form: { token: string; email: string; password: string }) {
    await authApi.resetPassword(form)
  }

  /** Выход на этом устройстве. Сеть недоступна — всё равно выходим локально */
  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // токен останется на сервере до срока жизни; локально выходим в любом случае
    } finally {
      forget()
    }
  }

  // Токен перестал действовать на любом запросе (вышли на другом устройстве, истёк срок)
  onUnauthorized(forget)

  return {
    user,
    status,
    isLoggedIn,
    restore,
    register,
    login,
    logout,
    forgotPassword,
    resetPassword,
  }
})
