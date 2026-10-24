import { readFileSync } from 'node:fs'

/**
 * Курс C# из ../content/csharp — тот же, что загружен в базу стека (course:import). Тесты берут
 * отсюда адрес урока и эталонное решение, а не копируют их: правка урока не ломает e2e.
 */
const CONTENT = new URL('../../content/csharp/', import.meta.url)

const read = (path: string) => readFileSync(new URL(path, CONTENT), 'utf8')
const readJson = <T>(path: string) => JSON.parse(read(path)) as T

interface Lesson {
  title: { ru: string }
  steps: { type: string; task?: string }[]
}

export interface CourseTask {
  /** Адрес шага с задачей: /courses/csharp/01-first-programs/01-hello?step=3 */
  url: string
  lessonTitle: string
  title: string
  /** Файл задачи: solution.cs, wrong-no-semicolon.cs… */
  code(file: string): string
}

export function courseTask(module: string, lesson: string, task: string): CourseTask {
  const course = readJson<{ slug: string }>('course.json').slug
  const lessonDir = `${module}/${lesson}`
  const { title, steps } = readJson<Lesson>(`${lessonDir}/lesson.json`)
  const step = steps.findIndex((item) => item.type === 'task' && item.task === task) + 1
  if (step === 0) {
    throw new Error(`В ${lessonDir}/lesson.json нет задачи ${task}`)
  }
  const taskDir = `${lessonDir}/tasks/${task}`

  return {
    url: `/courses/${course}/${module}/${lesson}?step=${step}`,
    lessonTitle: title.ru,
    title: readJson<{ title: { ru: string } }>(`${taskDir}/task.json`).title.ru,
    code: (file) => read(`${taskDir}/${file}`),
  }
}

/** Курс английского из ../content/english — практика урока: адрес шага и её упражнения */
const ENGLISH = new URL('../../content/english/', import.meta.url)

interface ChoiceExercise {
  kind: 'choice'
  prompt: { ru: string }
  options: { text: string }[]
  answer: number
}

export interface EnglishPractice {
  /** Адрес шага с практикой: /courses/english/01-simple-tenses/01-present-simple?step=3 */
  url: string
  lessonTitle: string
  exercises: ({ kind: string } | ChoiceExercise)[]
}

export function englishPractice(module: string, lesson: string, id: string): EnglishPractice {
  const lessonDir = new URL(`${module}/${lesson}/`, ENGLISH)
  const json = <T>(file: string) => JSON.parse(readFileSync(new URL(file, lessonDir), 'utf8')) as T
  const { title, steps } = json<{
    title: { ru: string }
    steps: { type: string; id?: string; file?: string }[]
  }>('lesson.json')
  const index = steps.findIndex((item) => item.type === 'practice' && item.id === id)
  if (index === -1) {
    throw new Error(`В english/${module}/${lesson}/lesson.json нет практики ${id}`)
  }

  return {
    url: `/courses/english/${module}/${lesson}?step=${index + 1}`,
    lessonTitle: title.ru,
    exercises: json(steps[index]!.file!),
  }
}

export interface EnglishWords {
  /** Адрес шага «Новые слова»: /courses/english/01-simple-tenses/01-present-simple?step=2 */
  url: string
  words: { id: string; word: string; translation: { ru: string } }[]
}

/** Карточки слов урока английского: адрес шага и сами карточки */
export function englishWords(module: string, lesson: string): EnglishWords {
  const lessonDir = new URL(`${module}/${lesson}/`, ENGLISH)
  const json = <T>(file: string) => JSON.parse(readFileSync(new URL(file, lessonDir), 'utf8')) as T
  const { steps } = json<{ steps: { type: string; file?: string }[] }>('lesson.json')
  const index = steps.findIndex((item) => item.type === 'words')
  if (index === -1) {
    throw new Error(`В english/${module}/${lesson}/lesson.json нет шага words`)
  }

  return {
    url: `/courses/english/${module}/${lesson}?step=${index + 1}`,
    words: json(steps[index]!.file!),
  }
}
