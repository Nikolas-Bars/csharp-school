import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import { tokenStorage } from '@/api/tokenStorage'
import LoginView from '@/views/LoginView.vue'

const fetchMock = vi.fn<typeof fetch>()

function reply(status: number, body: object) {
  return new Response(
    JSON.stringify({ data: null, errors: {}, message: '', status: 'error', ...body }),
    { status },
  )
}

async function mountLogin(path = '/login') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: {} },
      { path: '/login', name: 'login', component: LoginView },
      { path: '/register', name: 'register', component: {} },
      { path: '/lessons/1', component: {} },
    ],
  })
  await router.push(path)
  const wrapper = mount(LoginView, { global: { plugins: [createPinia(), router] } })
  return { wrapper, router }
}

async function fill(wrapper: Awaited<ReturnType<typeof mountLogin>>['wrapper']) {
  await wrapper.get('input[type="email"]').setValue('anya@example.com')
  await wrapper.get('input[type="password"]').setValue('password1')
  await wrapper.get('form').trigger('submit')
  await flushPromises()
}

describe('LoginView', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    tokenStorage.clear()
  })

  afterEach(() => {
    fetchMock.mockReset()
    vi.unstubAllGlobals()
  })

  it('поля подписаны и подсказывают браузеру, что заполнять', async () => {
    const { wrapper } = await mountLogin()
    const email = wrapper.get('input[type="email"]')

    expect(wrapper.get(`label[for="${email.attributes('id')}"]`).text()).toBe('Почта')
    expect(email.attributes('autocomplete')).toBe('email')
    expect(wrapper.get('input[type="password"]').attributes('autocomplete')).toBe(
      'current-password',
    )
  })

  it('неверный пароль — общий текст над формой', async () => {
    fetchMock.mockResolvedValue(reply(401, { message: 'Неверная почта или пароль.' }))
    const { wrapper } = await mountLogin()

    await fill(wrapper)

    expect(wrapper.get('[role="alert"]').text()).toBe('Неверная почта или пароль.')
  })

  it('ошибка поля — под полем и связана с ним для экранного диктора', async () => {
    fetchMock.mockResolvedValue(
      reply(422, {
        message: 'Проверь, правильно ли заполнены поля.',
        errors: { email: ['Заполни поле «Почта».'] },
      }),
    )
    const { wrapper } = await mountLogin()

    await fill(wrapper)

    const email = wrapper.get('input[type="email"]')
    expect(email.attributes('aria-invalid')).toBe('true')
    expect(wrapper.get(`#${email.attributes('aria-describedby')}`).text()).toBe(
      'Заполни поле «Почта».',
    )
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('после входа возвращает туда, откуда пришли, но только внутри сайта', async () => {
    const session = {
      user: { id: 1, nickname: 'anya', email: 'anya@example.com', locale: 'ru' },
      token: '1|x',
    }
    fetchMock.mockImplementation(async () => reply(200, { status: 'success', data: session }))

    const back = await mountLogin('/login?redirect=/lessons/1')
    await fill(back.wrapper)
    expect(back.router.currentRoute.value.fullPath).toBe('/lessons/1')

    const evil = await mountLogin('/login?redirect=//evil.example')
    await fill(evil.wrapper)
    expect(evil.router.currentRoute.value.fullPath).toBe('/')
  })
})
