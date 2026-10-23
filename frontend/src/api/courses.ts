import { http } from '@/api/http'
import type { Course, CourseSummary, Lesson, Page } from '@/types/api'

/** Модуль Course бэкенда: app/Modules/Course. Открыт гостям */
export const coursesApi = {
  list: (cursor: string | null = null) =>
    http.get<Page<CourseSummary>>(
      cursor === null ? 'courses' : `courses?cursor=${encodeURIComponent(cursor)}`,
    ),
  course: (course: string) => http.get<Course>(`courses/${encodeURIComponent(course)}`),
  lesson: (course: string, module: string, lesson: string) =>
    http.get<Lesson>(
      `courses/${encodeURIComponent(course)}/modules/${encodeURIComponent(module)}/lessons/${encodeURIComponent(lesson)}`,
    ),
}
