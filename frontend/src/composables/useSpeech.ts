import { computed, readonly, ref, shallowRef } from 'vue'

/** Произношение курса — американское (plans: решение 13) */
export const SPEECH_LANG = 'en-US'

/** Чуть медленнее обычного: ученик слушает, как произносится фраза */
const RATE = 0.9

const voice = shallowRef<SpeechSynthesisVoice | null>(null)
/** Фраза, которая звучит сейчас: у её кнопки — состояние «играет» */
const speaking = ref<string | null>(null)
/** Фраза, которая звучит: конец старой фразы после cancel() не сбрасывает новую, даже с тем же текстом */
let current: SpeechSynthesisUtterance | null = null
let started = false

function synth(): SpeechSynthesis | null {
  return typeof window !== 'undefined' && 'speechSynthesis' in window
    ? window.speechSynthesis
    : null
}

/** Голос: сначала американский, потом любой английский; нет — озвучки нет */
function pickVoice() {
  const voices = synth()?.getVoices() ?? []
  const lang = (item: SpeechSynthesisVoice) => item.lang.replace('_', '-').toLowerCase()
  voice.value =
    voices.find((item) => lang(item) === SPEECH_LANG.toLowerCase()) ??
    voices.find((item) => lang(item).startsWith('en')) ??
    null
}

/**
 * Озвучка английских фраз встроенным синтезом речи браузера (speechSynthesis): бесплатно и без
 * сервера. Голоса браузер отдаёт не сразу — ждём `voiceschanged`. На устройстве нет английского
 * голоса — `available` ложно, и кнопки звука нет. Состояние общее на страницу: одновременно
 * звучит одна фраза; `stop` — замолчать (кнопка ушла со страницы).
 */
export function useSpeech() {
  const engine = synth()
  if (engine && !started) {
    started = true
    pickVoice()
    engine.addEventListener('voiceschanged', pickVoice)
  }

  function speak(text: string) {
    const selected = voice.value
    if (!engine || !selected) {
      return
    }
    engine.cancel()
    const utterance = new SpeechSynthesisUtterance(text)
    utterance.voice = selected
    utterance.lang = selected.lang
    utterance.rate = RATE
    utterance.onend = utterance.onerror = () => {
      if (current === utterance) {
        current = null
        speaking.value = null
      }
    }
    current = utterance
    speaking.value = text
    engine.speak(utterance)
  }

  function stop() {
    if (engine && current) {
      current = null
      speaking.value = null
      engine.cancel()
    }
  }

  return {
    available: computed(() => voice.value !== null),
    speaking: readonly(speaking),
    speak,
    stop,
  }
}
