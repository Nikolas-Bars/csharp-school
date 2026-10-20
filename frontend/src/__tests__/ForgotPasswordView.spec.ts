import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { tokenStorage } from '@/api/tokenStorage'
import ForgotPasswordView from '@/views/ForgotPasswordView.vue'

import { testRouter } from './support/router'

const fetchMock = vi.fn<typeof fetch>()

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

async function mountForgot(path = '/forgot-password') {
  const router = await testRouter(path)
  const wrapper = mount(ForgotPasswordView, { global: { plugins: [createPinia(), router] } })
  return { wrapper, router }
}

describe('ForgotPasswordView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    tokenStorage.clear()
  })

  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('поле почты подписано, а почта со страницы входа уже вписана', async () => {
    const { wrapper } = await mountForgot('/forgot-password?email=anya@example.com')
    const email = wrapper.get('input[type="email"]')

    expect(wrapper.get(`label[for="${email.attributes('id')}"]`).text()).toBe('Почта')
    expect(email.attributes('autocomplete')).toBe('email')
    expect((email.element as HTMLInputElement).value).toBe('anya@example.com')
  })

  it('отправляет почту и говорит, что письмо в пути, — не раскрывая, есть ли аккаунт', async () => {
    fetchMock.mockResolvedValue(reply(200))
    const { wrapper } = await mountForgot()

    await wrapper.get('input[type="email"]').setValue('anya@example.com')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toMatch(/\/api\/auth\/forgot-password$/)
    expect(JSON.parse(String(init?.body))).toEqual({ email: 'anya@example.com' })

    expect(wrapper.get('[role="status"]').text()).toContain(
      'Если аккаунт с почтой anya@example.com есть, письмо со ссылкой уже в пути.',
    )
    // Форма остаётся: письмо можно запросить ещё раз
    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.get('a[href="/login"]').text()).toBe('Войти')
  })

  it('ошибка поля — под полем и связана с ним', async () => {
    fetchMock.mockResolvedValue(
      reply(422, {
        message: 'Проверь, правильно ли заполнены поля.',
        errors: { email: ['Заполни поле «Почта».'] },
      }),
    )
    const { wrapper } = await mountForgot()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const email = wrapper.get('input[type="email"]')
    expect(email.attributes('aria-invalid')).toBe('true')
    expect(wrapper.get(`#${email.attributes('aria-describedby')}`).text()).toBe(
      'Заполни поле «Почта».',
    )
    expect(wrapper.find('[role="status"]').exists()).toBe(false)
  })

  it('слишком много попыток — общий текст над формой', async () => {
    fetchMock.mockResolvedValue(
      reply(429, { message: 'Слишком много попыток. Подожди минуту и попробуй снова.' }),
    )
    const { wrapper } = await mountForgot()

    await wrapper.get('input[type="email"]').setValue('anya@example.com')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('[role="alert"]').text()).toBe(
      'Слишком много попыток. Подожди минуту и попробуй снова.',
    )
    expect(wrapper.find('form').exists()).toBe(true)
  })
})
