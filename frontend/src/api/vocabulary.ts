import { http } from '@/api/http'
import type { NewWord, Vocabulary, WordReview } from '@/types/api'

const base = (course: string) => `vocabulary/courses/${encodeURIComponent(course)}`

/** Модуль Vocabulary бэкенда: app/Modules/Vocabulary. Только для вошедшего ученика */
export const vocabularyApi = {
  show: (course: string) => http.get<Vocabulary>(base(course)),
  /** guest — словарь гостя после входа: своё расписание только для слов, которых в аккаунте нет */
  add: (course: string, words: NewWord[], guest = false) =>
    http.post<Vocabulary>(`${base(course)}/words`, guest ? { words, guest } : { words }),
  review: (course: string, reviews: WordReview[]) =>
    http.post<Vocabulary>(`${base(course)}/reviews`, { reviews }),
}
