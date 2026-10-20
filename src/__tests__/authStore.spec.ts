import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { http } from '@/api/http'
import { tokenStorage } from '@/api/tokenStorage'
import { DEFAULT_LOCALE, locale, setLocale } from '@/i18n/locale'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/api'

const fetchMock = vi.fn<typeof fetch>()
const anya: User = { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'en' }

function reply(status: number, data: unknown = null, message = '') {
  return new Response(
    JSON.stringify({ status: status < 400 ? 'success' : 'error', message, data, errors: {} }),
    {
      status,
    },
  )
}

describe('useAuthStore', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    tokenStorage.clear()
    setActivePinia(createPinia())
  })

  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
    setLocale(DEFAULT_LOCALE)
  })

  it('без токена — сразу гость, без запросов', async () => {
    const auth = useAuthStore()
    await auth.restore()

    expect(auth.status).toBe('guest')
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it('вход сохраняет токен, ученика и его язык', async () => {
    fetchMock.mockResolvedValue(reply(200, { user: anya, token: '1|secret' }))
    const auth = useAuthStore()

    await auth.login({ email: 'anya@example.com', password: 'password1' })

    expect(auth.user).toEqual(anya)
    expect(auth.status).toBe('user')
    expect(tokenStorage.get()).toBe('1|secret')
    expect(locale.value).toBe('en')
  })

  it('сохранённый токен восстанавливает вход один раз', async () => {
    tokenStorage.set('1|secret')
    fetchMock.mockResolvedValue(reply(200, anya))
    const auth = useAuthStore()
    expect(auth.status).toBe('unknown')

    await Promise.all([auth.restore(), auth.restore()])

    expect(auth.user).toEqual(anya)
    expect(fetchMock).toHaveBeenCalledTimes(1)
    const [, init] = fetchMock.mock.calls[0]!
    expect(init?.headers).toMatchObject({ Authorization: 'Bearer 1|secret' })
  })

  it('недействительный токен забывается', async () => {
    tokenStorage.set('1|expired')
    fetchMock.mockResolvedValue(reply(401, null, 'Сначала войди в аккаунт.'))
    const auth = useAuthStore()

    await auth.restore()

    expect(auth.status).toBe('guest')
    expect(tokenStorage.get()).toBeNull()
  })

  it('без сети — гость, но токен остаётся для следующей попытки', async () => {
    tokenStorage.set('1|secret')
    fetchMock.mockRejectedValue(new TypeError('Failed to fetch'))
    const auth = useAuthStore()

    await auth.restore()

    expect(auth.status).toBe('guest')
    expect(tokenStorage.get()).toBe('1|secret')
  })

  it('выход очищает сессию, даже если сервер недоступен', async () => {
    fetchMock.mockResolvedValueOnce(reply(200, { user: anya, token: '1|secret' }))
    const auth = useAuthStore()
    await auth.login({ email: 'anya@example.com', password: 'password1' })

    fetchMock.mockRejectedValueOnce(new TypeError('Failed to fetch'))
    await auth.logout()

    expect(auth.user).toBeNull()
    expect(tokenStorage.get()).toBeNull()
  })

  it('401 на любом запросе выводит из аккаунта', async () => {
    fetchMock.mockResolvedValueOnce(reply(200, { user: anya, token: '1|secret' }))
    const auth = useAuthStore()
    await auth.login({ email: 'anya@example.com', password: 'password1' })

    // Обычный запрос другого модуля получает 401 — стор узнаёт об этом через onUnauthorized
    fetchMock.mockResolvedValueOnce(reply(401))
    await http.get('progress').catch(() => undefined)

    expect(auth.status).toBe('guest')
    expect(auth.user).toBeNull()
    expect(tokenStorage.get()).toBeNull()
  })
})
