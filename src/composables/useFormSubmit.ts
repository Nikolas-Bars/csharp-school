import { ref } from 'vue'

import { ApiError } from '@/api/http'

/**
 * Отправка формы: пока идёт запрос — submitting; ошибки валидации бэкенда — по полям (первая
 * для каждого), остальные (неверный пароль, нет сети) — общим текстом над формой.
 */
export function useFormSubmit() {
  const submitting = ref(false)
  const fieldErrors = ref<Record<string, string>>({})
  const formError = ref('')

  async function submit(action: () => Promise<void>): Promise<boolean> {
    submitting.value = true
    fieldErrors.value = {}
    formError.value = ''

    try {
      await action()
      return true
    } catch (error) {
      if (!(error instanceof ApiError)) {
        throw error
      }
      const fields = Object.entries(error.errors).flatMap(([field, messages]) =>
        messages[0] === undefined ? [] : [[field, messages[0]] as const],
      )
      fieldErrors.value = Object.fromEntries(fields)
      formError.value = fields.length > 0 ? '' : error.message
      return false
    } finally {
      submitting.value = false
    }
  }

  return { submitting, fieldErrors, formError, submit }
}
