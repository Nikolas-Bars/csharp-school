import { http } from '@/api/http'
import type { AuthSession, User } from '@/types/api'

/** Модуль Auth бэкенда: app/Modules/Auth */
export const authApi = {
  register: (form: { nickname: string; email: string; password: string }) =>
    http.post<AuthSession>('auth/register', form),
  login: (form: { email: string; password: string }) => http.post<AuthSession>('auth/login', form),
  logout: () => http.post<null>('auth/logout'),
  me: () => http.get<User>('auth/me'),
}
