/** Ответ бэкенда (ApiResponder): { status, message, data, errors } */
export interface ApiResponse<T> {
  status: 'success' | 'error'
  message: string
  data: T
  errors: FieldErrors
}

/** Ошибки валидации: поле формы → список сообщений */
export type FieldErrors = Record<string, string[]>

/** Ученик — UserResource модуля Auth */
export interface User {
  id: number
  nickname: string
  email: string
  locale: string
}

/** Ответ на регистрацию и вход — SessionResource: токен для Authorization: Bearer */
export interface AuthSession {
  user: User
  token: string
}
