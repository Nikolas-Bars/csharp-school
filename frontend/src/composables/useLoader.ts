import { ref, shallowRef, watch, type WatchSource } from 'vue'

import { ApiError } from '@/api/http'

/**
 * Данные страницы с сервера: загрузка, ошибка (текст бэкенда уже на языке ученика), повтор.
 * Перезагружается, когда меняется source (например, параметры маршрута).
 */
export function useLoader<T, S>(source: WatchSource<S>, load: (value: S) => Promise<T>) {
  const data = shallowRef<T | null>(null)
  const error = ref<ApiError | null>(null)
  const loading = ref(true)
  let current = 0

  async function run(value: S) {
    const request = ++current
    loading.value = true
    error.value = null
    try {
      const result = await load(value)
      // Ответ на устаревший запрос (ученик успел перейти дальше) не показываем
      if (request === current) {
        data.value = result
      }
    } catch (caught) {
      if (request !== current) {
        return
      }
      if (!(caught instanceof ApiError)) {
        throw caught
      }
      data.value = null
      error.value = caught
    } finally {
      if (request === current) {
        loading.value = false
      }
    }
  }

  watch(source, (value) => void run(value), { immediate: true })

  return { data, error, loading }
}
