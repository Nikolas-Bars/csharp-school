import { ref, watch } from 'vue'

const PREFIX = 'draft:'

/**
 * Код задачи, который ученик ещё пишет: живёт в браузере, чтобы не пропасть при перезагрузке
 * или переходе к теории. Хранилище недоступно (приватный режим) — просто без сохранения.
 */
export function useDraft(key: string, initial: string) {
  const storageKey = PREFIX + key
  const code = ref(read() ?? initial)

  function read(): string | null {
    try {
      return localStorage.getItem(storageKey)
    } catch {
      return null
    }
  }

  watch(code, (value) => {
    try {
      if (value === initial) {
        localStorage.removeItem(storageKey)
      } else {
        localStorage.setItem(storageKey, value)
      }
    } catch {
      // без сохранения
    }
  })

  return code
}
