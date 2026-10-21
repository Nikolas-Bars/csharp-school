import { readFileSync } from 'node:fs'

/**
 * Учебный курс из ../content — тот же, что загружен в базу стека (course:import). Тесты берут
 * отсюда адрес урока и эталонное решение, а не копируют их: правка урока не ломает e2e.
 */
const CONTENT = new URL('../../content/', import.meta.url)

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
