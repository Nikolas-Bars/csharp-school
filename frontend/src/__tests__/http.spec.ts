import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { ApiError, http } from '@/api/http'

const fetchMock = vi.fn<typeof fetch>()

function reply(status: number, body?: unknown): Response {
  return new Response(body === undefined ? null : JSON.stringify(body), { status })
}

/** Ошибка, которой завершился запрос: проверяем поля ApiError, а не только текст */
async function failure(promise: Promise<unknown>): Promise<ApiError> {
  const error = await promise.then(
    () => null,
    (reason: unknown) => reason,
  )
  expect(error).toBeInstanceOf(ApiError)
  return error as ApiError
}

describe('http', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
  })

  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('разворачивает ответ до data и шлёт язык', async () => {
    fetchMock.mockResolvedValue(reply(200, { status: 'success', message: 'OK', data: { id: 7 } }))

    await expect(http.get<{ id: number }>('/users/me')).resolves.toEqual({ id: 7 })

    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toMatch(/\/api\/users\/me$/)
    expect(init?.headers).toMatchObject({ Accept: 'application/json', 'Accept-Language': 'ru' })
  })

  it('отправляет тело как JSON', async () => {
    fetchMock.mockResolvedValue(reply(201, { status: 'success', message: 'OK', data: null }))

    await http.post('auth/register', { name: 'Аня' })

    const [, init] = fetchMock.mock.calls[0]!
    expect(init?.body).toBe('{"name":"Аня"}')
    expect(init?.headers).toMatchObject({ 'Content-Type': 'application/json' })
  })

  it('204 без тела — не ошибка', async () => {
    fetchMock.mockResolvedValue(reply(204))

    await expect(http.delete('auth/logout')).resolves.toBeUndefined()
  })

  it('ошибка бэкенда — ApiError с его текстом, кодом и полями', async () => {
    fetchMock.mockResolvedValue(
      reply(422, {
        status: 'error',
        message: 'Проверьте правильность заполнения полей.',
        data: null,
        errors: { email: ['Укажи email.'] },
      }),
    )

    const error = await failure(http.post('auth/register', {}))
    expect(error.message).toBe('Проверьте правильность заполнения полей.')
    expect(error.status).toBe(422)
    expect(error.errors).toEqual({ email: ['Укажи email.'] })
  })

  it('ответ не JSON — ApiError с кодом ответа', async () => {
    fetchMock.mockResolvedValue(new Response('<html>Bad Gateway</html>', { status: 502 }))

    const error = await failure(http.get('courses'))
    expect(error.message).toBe('Не получилось выполнить запрос (код 502).')
    expect(error.status).toBe(502)
  })

  it('нет сети — ApiError с кодом 0', async () => {
    fetchMock.mockRejectedValue(new TypeError('Failed to fetch'))

    const error = await failure(http.get('courses'))
    expect(error.message).toBe('Нет связи с сервером. Проверь интернет.')
    expect(error.status).toBe(0)
  })
})
