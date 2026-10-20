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

export interface LessonSummary {
  slug: string
  title: string
  /** Сколько в уроке задач с кодом */
  tasks: number
}

/** Урок — LessonResource модуля Course: тексты уже на языке запроса */
export interface Lesson {
  slug: string
  title: string
  module: { slug: string; title: string }
  course: { slug: string; title: string }
  steps: LessonStep[]
}

export type LessonStep = TheoryStep | TaskStep | QuizStep

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
