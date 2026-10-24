import type { Check } from '@/runner/protocol'

/** Ответ бэкенда (ApiResponder): { status, message, data, errors } */
export interface ApiResponse<T> {
  status: 'success' | 'error'
  message: string
  data: T
  errors: FieldErrors
}

/** Ошибки валидации: поле формы → список сообщений */
export type FieldErrors = Record<string, string[]>

/** Ученик — UserResource модуля Auth */
export interface User {
  id: number
  nickname: string
  email: string
  locale: string
}

/** Ответ на регистрацию и вход — SessionResource: токен для Authorization: Bearer */
export interface AuthSession {
  user: User
  token: string
}

/** Страница списка — ApiResponder::page: следующая страница — ?cursor=next_cursor */
export interface Page<T> {
  items: T[]
  next_cursor: string | null
}

/** Курс на главной — CourseSummaryResource модуля Course */
export interface CourseSummary {
  slug: string
  title: string
  description: string
  /** Сколько модулей и уроков (вместе с контрольными) */
  modules: number
  lessons: number
  /** Первый урок курса — с него начинают */
  start: { module: string; lesson: string } | null
}

/** Карта курса — CourseResource модуля Course */
export interface Course {
  slug: string
  title: string
  modules: CourseModule[]
}

export interface CourseModule {
  slug: string
  title: string
  description: string
  lessons: LessonSummary[]
}

/** Вид урока: обычный или контрольная модуля — она последняя, после неё модуль пройден */
export type LessonKind = 'lesson' | 'test'

export interface LessonSummary {
  slug: string
  title: string
  kind: LessonKind
  /** Сколько в уроке задач с кодом */
  tasks: number
  /** Сколько в уроке карточек новых слов (английский) */
  words: number
  /** Засчитываемые шаги: id задач и практик и «quiz». Урок пройден, когда пройдены все */
  steps: string[]
}

/** Урок — LessonResource модуля Course: тексты уже на языке запроса */
export interface Lesson {
  slug: string
  title: string
  module: { slug: string; title: string }
  course: { slug: string; title: string }
  steps: LessonStep[]
}

export type LessonStep =
  TheoryStep | TaskStep | WordsStep | PracticeStep | QuizStep | ExerciseQuizStep

/** Экран теории: Markdown */
export interface TheoryStep {
  type: 'theory'
  text: string
}

/** Задача с кодом: проверки выполняет runner в браузере */
export interface TaskStep {
  type: 'task'
  id: string
  title: string
  /** Условие: Markdown */
  statement: string
  starter: string
  checks: Check[]
  /** Что не так и куда смотреть — по проверке, в том же порядке */
  messages: string[]
  /** Ровно три: намёк → подробнее → почти решение */
  hints: string[]
}

export interface QuizStep {
  type: 'quiz'
  questions: QuizQuestion[]
}

export interface QuizQuestion {
  question: string
  options: string[]
  answer: number
  explanation: string
}

/** Часть речи слова — название в локалях: words.pos.* */
export type PartOfSpeech =
  | 'noun'
  | 'verb'
  | 'adjective'
  | 'adverb'
  | 'pronoun'
  | 'preposition'
  | 'conjunction'
  | 'determiner'
  | 'auxiliary'
  | 'phrase'

/** Карточка слова — WordCardResource модуля Course: тексты для ученика уже на его языке */
export interface WordCard {
  /** Slug слова — один на курс, по нему слово помнит словарь */
  id: string
  word: string
  /** Американская транскрипция: /wɛr/ */
  ipa: string
  pos: PartOfSpeech
  translation: string
  /** Три формы неправильного глагола; у остальных слов — null */
  forms: string[] | null
  examples: { en: string; translation: string; why: string }[]
  /** «Не путай с…»: **жирный** и ==маркер== как в теории */
  compare: string | null
}

/** Новые слова урока английского: карточки по одной, в прогресс не засчитываются */
export interface WordsStep {
  type: 'words'
  words: WordCard[]
}

/** Слово курса для словаря — WordResource: карточка и урок, где оно появилось */
export interface CourseWord extends WordCard {
  module: { slug: string; title: string }
  lesson: { slug: string; title: string }
}

/** Слово в словаре ученика: коробка повторения и когда повторить (ISO 8601) */
export interface LearnedWord {
  word: string
  /** 0 — новое или забытое … 4 — выучено */
  box: number
  due_at: string
  reviewed_at: string | null
}

/** Словарь по курсу — VocabularyResource модуля Vocabulary */
export interface Vocabulary {
  words: LearnedWord[]
}

/** Новое слово для словаря; у словаря гостя после входа — с его расписанием */
export interface NewWord {
  word: string
  box?: number
  due_at?: string
}

/** Итог повторения слова: «Помню» или «Не помню» */
export interface WordReview {
  word: string
  remembered: boolean
}

/** Практика английского: упражнения по одному, проверка — в браузере (utils/answers.ts) */
export interface PracticeStep {
  type: 'practice'
  id: string
  exercises: Exercise[]
}

/** Проверка знаний в конце урока английского — те же упражнения, что в практике */
export interface ExerciseQuizStep {
  type: 'quiz'
  exercises: Exercise[]
}

export type Exercise = ChoiceExercise | WrittenExercise

interface ExerciseBase {
  /** Задание: что сделать */
  prompt: string
  /** Почему верный ответ верный: после верного ответа и рядом с показанным ответом */
  explanation: string
}

/** Выбрать вариант; у неверного варианта — объяснение, почему он не подходит */
export interface ChoiceExercise extends ExerciseBase {
  kind: 'choice'
  options: { text: string; why: string | null }[]
  answer: number
}

/** Частая ошибка и её объяснение */
export interface Mistake {
  answer: string
  why: string
}

/** Ответ словами: правильные ответы, частые ошибки и подсказка для остальных неверных */
interface WrittenBase extends ExerciseBase {
  /** Правильные ответы; {a|b} — любой из вариантов */
  accept: string[]
  mistakes: Mistake[]
  /** Неверный ответ не из mistakes — без готового ответа */
  hint: string
  /** Краткие и полные формы различаются (задание на краткую форму) */
  exact: boolean
}

/** Вставить слово в пропуск ___ английской фразы */
export interface GapExercise extends WrittenBase {
  kind: 'gap'
  text: string
}

/** Собрать фразу из плиток (бывают лишние) */
export interface OrderExercise extends WrittenBase {
  kind: 'order'
  tiles: string[]
}

/** Перевести фразу с языка ученика на английский */
export interface TranslateExercise extends WrittenBase {
  kind: 'translate'
  text: string
}

/** Найти слово с ошибкой (номер `wrong` среди слов `text` через пробел) и написать, как правильно */
export interface FixExercise extends WrittenBase {
  kind: 'fix'
  text: string
  wrong: number
}

export type WrittenExercise = GapExercise | OrderExercise | TranslateExercise | FixExercise

/** Прогресс по курсу — CourseProgressResource модуля Progress (без кода решений) */
export interface CourseProgress {
  lessons: { module: string; lesson: string; steps: string[]; completed: string[]; done: boolean }[]
}

/** Прогресс урока — LessonProgressResource: пройденные шаги и последний зачтённый код задач */
export interface LessonProgress {
  completed: string[]
  solutions: Record<string, string>
}

/** Пройденный шаг: для задачи — с кодом, который прошёл проверки */
export interface StepCompletion {
  module: string
  lesson: string
  step: string
  code?: string | null
}
