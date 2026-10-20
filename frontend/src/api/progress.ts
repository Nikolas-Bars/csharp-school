import { http } from '@/api/http'
import type { CourseProgress, LessonProgress, StepCompletion } from '@/types/api'

const base = (course: string) => `progress/courses/${encodeURIComponent(course)}`

/** Модуль Progress бэкенда: app/Modules/Progress. Только для вошедшего ученика */
export const progressApi = {
  course: (course: string) => http.get<CourseProgress>(base(course)),
  lesson: (course: string, module: string, lesson: string) =>
    http.get<LessonProgress>(
      `${base(course)}/modules/${encodeURIComponent(module)}/lessons/${encodeURIComponent(lesson)}`,
    ),
  /** guest — прогресс гостя после входа: дополняет аккаунт, сохранённый код не заменяет */
  complete: (course: string, steps: StepCompletion[], guest = false) =>
    http.post<CourseProgress>(`${base(course)}/steps`, guest ? { steps, guest } : { steps }),
}
