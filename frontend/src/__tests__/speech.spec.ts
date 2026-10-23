import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

/**
 * Озвучка: в jsdom нет speechSynthesis — подменяем. Состояние useSpeech общее на страницу,
 * поэтому каждый тест берёт модули заново (vi.resetModules).
 */
class FakeUtterance {
  voice: SpeechSynthesisVoice | null = null
  lang = ''
  rate = 1
  onend: (() => void) | null = null
  onerror: (() => void) | null = null
  constructor(public text: string) {}
}

const voice = (lang: string, name = lang) => ({ lang, name }) as SpeechSynthesisVoice

function fakeSynth(voices: SpeechSynthesisVoice[]) {
  const listeners: (() => void)[] = []
  const spoken: FakeUtterance[] = []
  const synth = {
    voices,
    getVoices: () => synth.voices,
    addEventListener: (_type: string, listener: () => void) => listeners.push(listener),
    speak: vi.fn<(utterance: FakeUtterance) => void>((utterance) => {
      spoken.push(utterance)
    }),
    cancel: vi.fn<() => void>(),
    /** Браузер догрузил голоса */
    load(next: SpeechSynthesisVoice[]) {
      synth.voices = next
      listeners.forEach((listener) => listener())
    },
  }
  vi.stubGlobal('speechSynthesis', synth)
  vi.stubGlobal('SpeechSynthesisUtterance', FakeUtterance)
  return { synth, spoken }
}

async function speechModule() {
  return import('@/composables/useSpeech')
}

describe('useSpeech', () => {
  beforeEach(() => vi.resetModules())
  afterEach(() => vi.unstubAllGlobals())

  it('выбирает американский голос, говорит чуть медленнее и помнит, что звучит', async () => {
    const { spoken, synth } = fakeSynth([
      voice('ru-RU'),
      voice('en-GB'),
      voice('en_US', 'Samantha'),
    ])
    const { useSpeech } = await speechModule()
    const speech = useSpeech()

    expect(speech.available.value).toBe(true)
    speech.speak('Look! It is raining.')

    expect(synth.cancel).toHaveBeenCalled()
    expect(spoken[0]).toMatchObject({ text: 'Look! It is raining.', rate: 0.9, lang: 'en_US' })
    expect(spoken[0]!.voice?.name).toBe('Samantha')
    expect(speech.speaking.value).toBe('Look! It is raining.')

    spoken[0]!.onend?.()
    expect(speech.speaking.value).toBeNull()
  })

  it('повтор той же фразы: конец старой после cancel не гасит новую; stop — тишина', async () => {
    const { spoken, synth } = fakeSynth([voice('en-US')])
    const { useSpeech } = await speechModule()
    const speech = useSpeech()

    speech.speak('Hello')
    speech.speak('Hello')
    spoken[0]!.onend?.()
    expect(speech.speaking.value).toBe('Hello')

    speech.stop()
    expect(speech.speaking.value).toBeNull()
    expect(synth.cancel).toHaveBeenCalledTimes(3)
  })

  it('нет американского — любой английский; голоса приходят позже', async () => {
    const { synth } = fakeSynth([])
    const { useSpeech } = await speechModule()
    const speech = useSpeech()

    expect(speech.available.value).toBe(false)
    synth.load([voice('de-DE'), voice('en-GB')])

    expect(speech.available.value).toBe(true)
  })

  it('без английского голоса или без speechSynthesis — озвучки нет', async () => {
    fakeSynth([voice('ru-RU')])
    expect((await speechModule()).useSpeech().available.value).toBe(false)

    vi.resetModules()
    vi.unstubAllGlobals()
    const { useSpeech } = await speechModule()
    const speech = useSpeech()
    expect(speech.available.value).toBe(false)
    expect(() => speech.speak('Hello')).not.toThrow()
  })
})

describe('BaseSpeakButton и MarkdownView', () => {
  beforeEach(() => vi.resetModules())
  afterEach(() => vi.unstubAllGlobals())

  it('пример в теории — фраза, перевод и кнопка «послушать»', async () => {
    const { spoken } = fakeSynth([voice('en-US')])
    const MarkdownView = (await import('@/components/lesson/MarkdownView.vue')).default
    const wrapper = mount(MarkdownView, {
      props: { source: '```example\nLook! It is raining.\nСмотри! Идёт дождь.\n```' },
    })

    expect(wrapper.get('[lang="en"]').text()).toBe('Look! It is raining.')
    expect(wrapper.text()).toContain('Смотри! Идёт дождь.')
    const button = wrapper.get('button')
    expect(button.attributes('aria-label')).toBe('Послушать: Look! It is raining.')

    await button.trigger('click')
    expect(spoken[0]?.text).toBe('Look! It is raining.')
    expect(button.classes()).toContain('speak--playing')
  })

  it('без голоса кнопки нет, фраза и перевод видны', async () => {
    fakeSynth([])
    const MarkdownView = (await import('@/components/lesson/MarkdownView.vue')).default
    const wrapper = mount(MarkdownView, {
      props: { source: '```example\nLook!\nСмотри!\n```' },
    })

    expect(wrapper.find('button').exists()).toBe(false)
    expect(wrapper.text()).toContain('Смотри!')
  })

  it('таблица, ошибка → правильно, заметка и слово-маркер', async () => {
    fakeSynth([])
    const MarkdownView = (await import('@/components/lesson/MarkdownView.vue')).default
    const wrapper = mount(MarkdownView, {
      props: {
        source: [
          '| Кто | Как |',
          '|---|---|',
          '| I | I **am** working |',
          '',
          '```mistake',
          'I working now.',
          'I am working now.',
          'Без am время не строится.',
          '```',
          '',
          '> Маркер ==now==.',
        ].join('\n'),
      },
    })

    expect(wrapper.findAll('th[scope="col"]').map((cell) => cell.text())).toEqual(['Кто', 'Как'])
    expect(wrapper.get('th[scope="row"]').text()).toBe('I')
    expect(wrapper.get('td strong').text()).toBe('am')
    expect(wrapper.get('s').text()).toBe('I working now.')
    expect(wrapper.get('.mistake__wrong').text()).toContain('Неверно:')
    expect(wrapper.get('.mistake__right').text()).toContain('I am working now.')
    expect(wrapper.get('.markdown__note mark').text()).toBe('now')
  })
})
