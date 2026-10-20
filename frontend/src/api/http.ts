import { t } from '@/i18n'
import { locale } from '@/i18n/locale'
import type { ApiResponse, FieldErrors } from '@/types/api'

/** Ошибка запроса: текст для ученика, HTTP-код (0 — нет связи) и ошибки по полям формы */
export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly errors: FieldErrors = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

const API_URL = (import.meta.env.VITE_API_URL ?? '').replace(/\/+$/, '')

/**
 * Единственное место, где фронт ходит в сеть. Бэкенд всегда отвечает
 * { status, message, data, errors }: успех разворачивается до data, остальное — ApiError
 * с текстом бэкенда (он уже на языке ученика).
 */
async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Accept-Language': locale.value,
  }
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }

  let response: Response
  try {
    response = await fetch(`${API_URL}/api/${path.replace(/^\/+/, '')}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch {
    throw new ApiError(t('errors.network'), 0)
  }

  // 204 — успех без тела
  if (response.status === 204) {
    return undefined as T
  }

  const payload = (await response.json().catch(() => null)) as ApiResponse<T> | null
  if (!response.ok || payload === null) {
    throw new ApiError(
      payload?.message || t('errors.requestFailed', { status: response.status }),
      response.status,
      payload?.errors,
    )
  }

  return payload.data
}

export const http = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, body),
  put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body),
  delete: <T>(path: string) => request<T>('DELETE', path),
}
