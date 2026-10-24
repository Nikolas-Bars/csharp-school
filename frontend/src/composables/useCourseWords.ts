import { computed, type Ref } from 'vue'

import { useLoader } from '@/composables/useLoader'
import { t } from '@/i18n'
import { locale } from '@/i18n/locale'
import { useCourseStore } from '@/stores/course'
import { useProgressStore } from '@/stores/progress'
import { useVocabularyStore } from '@/stores/vocabulary'
import type { Course, CourseWord } from '@/types/api'

/** Номер модуля и урока слова в курсе: «Модуль 2 · Урок 1» */
function positions(course: Course | null) {
  const map = new Map<string, { module: number; lesson: number }>()
  course?.modules.forEach((module, moduleIndex) =>
    module.lessons.forEach((lesson, lessonIndex) =>
      map.set(`${module.slug}/${lesson.slug}`, {
        module: moduleIndex + 1,
        lesson: lessonIndex + 1,
      }),
    ),
  )
  return map
}

/**
 * Словарь курса для страниц «Мой словарь» и «Повторение»: карточки, карта курса (номера модулей и
 * уроков), словарь ученика и прогресс. Слова уроков, пройденных до появления карточек (или без
 * шага слов), добавляются в словарь сами: словарь — это слова пройденных уроков.
 */
export function useCourseWords(course: Ref<string>) {
  const courses = useCourseStore()
  const progress = useProgressStore()
  const vocabulary = useVocabularyStore()

  const { data, error, loading } = useLoader(
    () => `${course.value}|${locale.value}`,
    async () => {
      const slug = course.value
      const [map, cards] = await Promise.all([
        courses.load(slug),
        vocabulary.loadCards(slug),
        vocabulary.load(slug),
        progress.load(slug),
      ])
      const missing = cards.filter((card) => {
        const lesson = map.modules
          .find((module) => module.slug === card.module.slug)
          ?.lessons.find((item) => item.slug === card.lesson.slug)
        return (
          lesson !== undefined &&
          !(card.id in vocabulary.entries) &&
          progress.isLessonDone(card.module.slug, card.lesson.slug, lesson.steps)
        )
      })
      if (missing.length > 0) {
        await vocabulary.add(
          slug,
          missing.map((card) => card.id),
        )
      }
      return { map, cards }
    },
  )

  const map = computed(() => courses.map(course.value) ?? data.value?.map ?? null)
  const cards = computed(() => vocabulary.cards(course.value) ?? data.value?.cards ?? [])
  const places = computed(() => positions(map.value))

  /** Слова словаря ученика по порядку курса — с расписанием */
  const learned = computed(() =>
    cards.value.flatMap((card) => {
      const entry = vocabulary.entries[card.id]
      return entry ? [{ card, entry }] : []
    }),
  )

  function placeOf(card: CourseWord) {
    return places.value.get(`${card.module.slug}/${card.lesson.slug}`) ?? null
  }

  /** «Модуль 2 · Урок 1», а без карты — название урока */
  function lessonLabel(card: CourseWord): string {
    const place = placeOf(card)
    return place ? t('words.lessonLabel', place) : card.lesson.title
  }

  return { loading, error, map, cards, learned, placeOf, lessonLabel }
}
