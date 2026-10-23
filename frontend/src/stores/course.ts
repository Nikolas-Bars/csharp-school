import { defineStore } from 'pinia'
import { shallowRef } from 'vue'

import { coursesApi } from '@/api/courses'
import { locale } from '@/i18n/locale'
import type { Course, CourseSummary } from '@/types/api'

/** Сколько карта считается свежей: после course:import вкладка не живёт со старой картой */
export const COURSE_MAP_TTL = 5 * 60_000

/** Все страницы списка курсов: курсов единицы, но список — по общему правилу, с курсором */
async function fetchAllCourses(): Promise<CourseSummary[]> {
  const courses: CourseSummary[] = []
  let cursor: string | null = null
  do {
    const page = await coursesApi.list(cursor)
    courses.push(...page.items)
    cursor = page.next_cursor
  } while (cursor !== null)
  return courses
}

/**
 * Список курсов (главная) и карта каждого курса. Список — по языку, как и карта.
 *
 * Карта курса: модули и уроки по порядку. Нужна и карте, и уроку (контрольная, следующий
 * модуль), поэтому хранится по курсу и языку: переходы между уроками запросов не шлют.
 * Устаревшая (старше COURSE_MAP_TTL) или запрошенная заново (`refresh` — заход на карту курса)
 * сразу отдаётся из памяти и обновляется в фоне; обновлённая заменяет старую у всех, кто её показывает.
 */
export const useCourseStore = defineStore('course', () => {
  /** «курс|язык» → карта; реактивно: обновление в фоне видно сразу */
  const maps = shallowRef<Record<string, { course: Course; at: number }>>({})
  /** Идущие загрузки — одна на «курс|язык» */
  const pending = new Map<string, Promise<Course>>()

  const keyOf = (slug: string) => `${slug}|${locale.value}`

  /** Язык → список курсов; правила те же, что у карты: из памяти, устаревший — обновить в фоне */
  const catalogs = shallowRef<Record<string, { courses: CourseSummary[]; at: number }>>({})
  const catalogPending = new Map<string, Promise<CourseSummary[]>>()

  function fetchCatalog(key: string): Promise<CourseSummary[]> {
    let request = catalogPending.get(key)
    if (!request) {
      request = fetchAllCourses()
        .then((courses) => {
          catalogs.value = { ...catalogs.value, [key]: { courses, at: Date.now() } }
          return courses
        })
        .finally(() => catalogPending.delete(key))
      catalogPending.set(key, request)
    }
    return request
  }

  /** Курсы платформы на текущем языке по порядку. Ошибка не запоминается */
  function loadCourses(): Promise<CourseSummary[]> {
    const key = locale.value
    const cached = catalogs.value[key]
    if (!cached) {
      return fetchCatalog(key)
    }
    if (Date.now() - cached.at > COURSE_MAP_TTL) {
      fetchCatalog(key).catch(() => {})
    }
    return Promise.resolve(cached.courses)
  }

  /** Загруженный список курсов на текущем языке (реактивно) или null */
  function courses(): CourseSummary[] | null {
    return catalogs.value[locale.value]?.courses ?? null
  }

  function fetchMap(slug: string, key: string): Promise<Course> {
    let request = pending.get(key)
    if (!request) {
      request = coursesApi
        .course(slug)
        .then((course) => {
          maps.value = { ...maps.value, [key]: { course, at: Date.now() } }
          return course
        })
        .finally(() => pending.delete(key))
      pending.set(key, request)
    }
    return request
  }

  /** Карта курса на текущем языке: из памяти, а нет — с сервера. Ошибка не запоминается */
  function load(slug: string, { refresh = false } = {}): Promise<Course> {
    const key = keyOf(slug)
    const cached = maps.value[key]
    if (!cached) {
      return fetchMap(slug, key)
    }
    if (refresh || Date.now() - cached.at > COURSE_MAP_TTL) {
      // В фоне: не загрузилась — остаётся прежняя
      fetchMap(slug, key).catch(() => {})
    }
    return Promise.resolve(cached.course)
  }

  /** Загруженная карта курса на текущем языке (реактивно) или null */
  function map(slug: string): Course | null {
    return maps.value[keyOf(slug)]?.course ?? null
  }

  return { load, map, loadCourses, courses }
})
