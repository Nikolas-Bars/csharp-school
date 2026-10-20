import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import ResetPasswordView from '@/views/ResetPasswordView.vue'

import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()
const LINK = '/reset-password?token=abc123&email=anya%40example.com'

function reply(status: number, body: object = {}) {
  return new Response(
    JSON.stringify({
      data: null,
      errors: {},
      message: '',
      status: status < 400 ? 'success' : 'error',
      ...body,
    }),
    { status },
  )
}

async function mountReset(path = LINK) {
  const router = await testRouter(path)
  const wrapper = mount(ResetPasswordView, { global: { plugins: [createPinia(), router] } })
  return { wrapper, router }
}

async function save(wrapper: Awaited<ReturnType<typeof mountReset>>['wrapper']) {
  await wrapper.get('input[type="password"]').setValue('новый пароль')
  await wrapper.get('form').trigger('submit')
  await flushPromises()
}

describe('ResetPasswordView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    tokenStorage.clear()
  })

  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('почта из ссылки — только для чтения, новый пароль подписан и с подсказкой', async () => {
    const { wrapper } = await mountReset()
    const email = wrapper.get('input[type="email"]')
    const password = wrapper.get('input[type="password"]')

    expect((email.element as HTMLInputElement).value).toBe('anya@example.com')
    expect(email.attributes('readonly')).toBeDefined()
    expect(email.attributes('autocomplete')).toBe('username')
    expect(wrapper.get(`label[for="${password.attributes('id')}"]`).text()).toBe('Новый пароль')
    expect(password.attributes('autocomplete')).toBe('new-password')
    expect(wrapper.get(`#${password.attributes('aria-describedby')}`).text()).toContain(
      'Не меньше 8 символов.',
    )
  })

  it('сохраняет пароль с токеном и почтой из ссылки и ведёт на вход с сообщением', async () => {
    fetchMock.mockResolvedValue(reply(200))
    const { wrapper, router } = await mountReset()

    await save(wrapper)

    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toMatch(/\/api\/auth\/reset-password$/)
    expect(JSON.parse(String(init?.body))).toEqual({
      token: 'abc123',
      email: 'anya@example.com',
      password: 'новый пароль',
    })
    expect(router.currentRoute.value.fullPath).toBe('/login?reset=done')
  })

  it('ссылка устарела — текст над формой и ссылка на новое письмо', async () => {
    const message =
      'Ссылка для нового пароля не подходит: она устарела или уже использована. Запроси новую.'
    fetchMock.mockResolvedValue(reply(400, { message }))
    const { wrapper, router } = await mountReset()

    await save(wrapper)

    expect(wrapper.get('[role="alert"]').text()).toBe(message)
    expect(router.currentRoute.value.name).toBe('reset-password')
    expect(wrapper.get('a[href="/forgot-password?email=anya@example.com"]').text()).toBe(
      'Запросить новую',
    )
  })

  it('слабый пароль — ошибка под полем и связана с ним', async () => {
    fetchMock.mockResolvedValue(
      reply(422, {
        message: 'Проверь, правильно ли заполнены поля.',
        errors: { password: ['В поле «Новый пароль» не меньше 8 символов.'] },
      }),
    )
    const { wrapper } = await mountReset()

    await save(wrapper)

    const password = wrapper.get('input[type="password"]')
    expect(password.attributes('aria-invalid')).toBe('true')
    expect(password.attributes('aria-describedby')).toContain('-error')
    expect(wrapper.text()).toContain('В поле «Новый пароль» не меньше 8 символов.')
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('ошибка токена из ссылки — над формой: такого поля на экране нет', async () => {
    fetchMock.mockResolvedValue(
      reply(422, {
        message: 'Проверь, правильно ли заполнены поля.',
        errors: { token: ['В поле «Ссылка из письма» не больше 255 символов.'] },
      }),
    )
    const { wrapper } = await mountReset()

    await save(wrapper)

    expect(wrapper.get('[role="alert"]').text()).toBe(
      'В поле «Ссылка из письма» не больше 255 символов.',
    )
  })

  it.each(['/reset-password', '/reset-password?token=abc123', '/reset-password?email=a@b.c'])(
    'неполная ссылка (%s) — без формы, с подсказкой запросить новую',
    async (path) => {
      const { wrapper } = await mountReset(path)

      expect(wrapper.find('form').exists()).toBe(false)
      expect(wrapper.get('[role="alert"]').text()).toBe(
        'В ссылке чего-то не хватает. Открой её из письма ещё раз или запроси новую.',
      )
      expect(wrapper.get('a').attributes('href')).toMatch(/^\/forgot-password/)
      expect(fetchMock).not.toHaveBeenCalled()
    },
  )
})
