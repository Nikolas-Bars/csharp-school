/**
 * Токен входа между перезагрузками страницы. localStorage бывает недоступен (приватный режим,
 * запрет сайтов) — тогда вход живёт до перезагрузки, а не ломает страницу.
 */
const KEY = 'auth_token'

export const tokenStorage = {
  get(): string | null {
    try {
      return localStorage.getItem(KEY)
    } catch {
      return null
    }
  },
  set(token: string): void {
    try {
      localStorage.setItem(KEY, token)
    } catch {
      // без сохранения: вход до перезагрузки
    }
  },
  clear(): void {
    try {
      localStorage.removeItem(KEY)
    } catch {
      // нечего удалять
    }
  },
}
