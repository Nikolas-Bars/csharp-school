import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { describe, expect, it } from 'vitest'

import type { Exercise } from '@/types/api'
import { checkAnswer, clean, expand, matches, words, type Answer } from '@/utils/answers'
import { parseMarkdown } from '@/utils/markdown'

/**
 * Тесты контента курса английского (content/english, формат — content/README.md). Ответы
 * проверяет та же функция, что у ученика в браузере (utils/answers.ts): каждый правильный ответ
 * проходит, каждая частая ошибка падает и показывает своё объяснение, у каждого неверного варианта
 * есть объяснение. Плюс структура: файлы на месте, лишних полей нет, контрольная в модуле одна и
 * последняя, проверка знаний — 2–4 упражнения в конце урока.
 *
 * Каждая проверка не падает сразу, а записывает проблему: автор урока видит все ошибки разом.
 * Путь — от папки фронта: в контейнере ../content смонтирован в /content (docker-compose.yml), в CI
 * это папка content в корне репозитория.
 */
const ROOT = resolve('../content')
const COURSE = 'english'

type Json = Record<string, unknown>

const path = (file: string) => resolve(ROOT, file)
const read = (file: string) => readFileSync(path(file), 'utf8')
const json = (file: string) => JSON.parse(read(file)) as unknown

const KINDS = ['choice', 'gap', 'order', 'translate', 'fix'] as const
type Kind = (typeof KINDS)[number]
const STEP_TYPES = ['theory', 'practice', 'quiz']
const SLUG = /^[a-z0-9][a-z0-9-]{0,63}$/
const FILE = /^[a-z0-9][a-z0-9-]*(\/[a-z0-9][a-z0-9-]*)?\.json$/
const CYRILLIC = /[а-яё]/i

/** Обязательные поля упражнений по виду; у упражнений со словами ещё необязательное forms */
const FIELDS: Record<Kind, string[]> = {
  choice: ['kind', 'prompt', 'options', 'answer', 'explanation'],
  gap: ['kind', 'prompt', 'text', 'accept', 'mistakes', 'hint', 'explanation'],
  order: ['kind', 'prompt', 'tiles', 'accept', 'mistakes', 'hint', 'explanation'],
  translate: ['kind', 'prompt', 'text', 'accept', 'mistakes', 'hint', 'explanation'],
  fix: ['kind', 'prompt', 'text', 'wrong', 'accept', 'mistakes', 'hint', 'explanation'],
}

const isObject = (value: unknown): value is Json =>
  typeof value === 'object' && value !== null && !Array.isArray(value)

const keys = (value: unknown) => (isObject(value) ? Object.keys(value) : [])

const ru = (value: unknown) => (isObject(value) && typeof value.ru === 'string' ? value.ru : '')

/** Найденные проблемы контента: «где — что не так» */
class Problems {
  readonly list: string[] = []

  /** Условие не выполнено — записать проблему */
  check(ok: boolean, message: string) {
    if (!ok) {
      this.list.push(message)
    }
    return ok
  }

  /** Тексты по языкам, русский обязателен, пустых нет */
  texts(value: unknown, where: string) {
    const entries = isObject(value) ? Object.values(value) : []
    return this.check(
      ru(value).trim() !== '' &&
        entries.every((text) => typeof text === 'string' && text.trim() !== ''),
      `${where}: тексты по языкам, русский обязателен — {"ru": "…"}`,
    )
  }

  /** Фраза по-английски: непустая, без кириллицы */
  english(text: unknown, where: string) {
    return this.check(
      typeof text === 'string' && text.trim() !== '' && !CYRILLIC.test(text),
      `${where}: английская фраза без кириллицы — «${String(text)}»`,
    )
  }

  /** Лишние и недостающие поля */
  fields(value: unknown, required: string[], optional: string[], where: string) {
    const present = keys(value)
    const extra = present.filter((key) => !required.includes(key) && !optional.includes(key))
    const missing = required.filter((key) => !present.includes(key))
    this.check(extra.length === 0, `${where}: лишние поля ${extra.join(', ')}`)
    return this.check(missing.length === 0, `${where}: нет полей ${missing.join(', ')}`)
  }
}

/** Упражнение из файла — в форму ответа API (русские тексты), как его получит браузер */
function toApi(raw: Json): Exercise {
  const base = { prompt: ru(raw.prompt), explanation: ru(raw.explanation) }
  if (raw.kind === 'choice') {
    return {
      ...base,
      kind: 'choice',
      answer: Number(raw.answer),
      options: (raw.options as Json[]).map((option) => ({
        text: String(option.text),
        why: isObject(option.why) ? ru(option.why) : null,
      })),
    }
  }
  const common = {
    ...base,
    accept: raw.accept as string[],
    mistakes: (raw.mistakes as Json[]).map((item) => ({
      answer: String(item.answer),
      why: ru(item.why),
    })),
    hint: ru(raw.hint),
    exact: raw.forms === 'exact',
  }
  switch (raw.kind) {
    case 'gap':
      return { ...common, kind: 'gap', text: String(raw.text) }
    case 'order':
      return { ...common, kind: 'order', tiles: raw.tiles as string[] }
    case 'translate':
      return { ...common, kind: 'translate', text: ru(raw.text) }
    default:
      return { ...common, kind: 'fix', text: String(raw.text), wrong: Number(raw.wrong) }
  }
}

/** Ответ ученика словами: у «найди ошибку» — с нужным словом */
function written(exercise: Exercise, text: string): Answer {
  return exercise.kind === 'fix' ? { word: exercise.wrong, text } : { text }
}

/** Фразу можно собрать из плиток: каждая плитка — целиком и не больше одного раза */
function buildable(phrase: string, tiles: string[]): boolean {
  const target = clean(phrase).split(' ')
  const pieces = tiles.map((tile) => clean(tile).split(' '))
  const used = new Set<number>()
  const search = (at: number): boolean =>
    at === target.length ||
    pieces.some((piece, index) => {
      if (used.has(index) || piece.some((word, offset) => target[at + offset] !== word)) {
        return false
      }
      used.add(index)
      const found = search(at + piece.length)
      used.delete(index)
      return found
    })
  return search(0)
}

function checkChoice(problems: Problems, raw: Json, exercise: Exercise, where: string) {
  if (exercise.kind !== 'choice') {
    return
  }
  const options = Array.isArray(raw.options) ? (raw.options as unknown[]) : []
  problems.check(options.length >= 2 && options.length <= 4, `${where}: 2–4 варианта`)
  if (!problems.check(options.every(isObject), `${where}.options: объекты { text, why }`)) {
    return
  }
  problems.check(
    Number.isInteger(raw.answer) && options[exercise.answer] !== undefined,
    `${where}.answer: номер верного варианта`,
  )
  ;(options as Json[]).forEach((option, index) => {
    const at = `${where}.options[${index}]`
    problems.fields(option, ['text'], ['why'], at)
    problems.english(option.text, `${at}.text`)
    const verdict = checkAnswer(exercise, { option: index })
    if (index === exercise.answer) {
      problems.check(
        !('why' in option),
        `${at}: у верного варианта why не нужен — есть explanation`,
      )
      problems.check(verdict.right, `${at}: верный вариант не проходит`)
    } else if (problems.texts(option.why, `${at}.why`)) {
      problems.check(
        !verdict.right && verdict.mistake === ru(option.why),
        `${at}: неверный вариант не показывает своё объяснение`,
      )
    }
  })
  const texts = (options as Json[]).map((option) => clean(String(option.text)))
  problems.check(new Set(texts).size === texts.length, `${where}: варианты повторяются`)
}

function checkWritten(problems: Problems, raw: Json, exercise: Exercise, where: string) {
  if (exercise.kind === 'choice') {
    return
  }
  problems.texts(raw.hint, `${where}.hint`)
  problems.check(!('forms' in raw) || raw.forms === 'exact', `${where}.forms: только "exact"`)
  const accept = Array.isArray(raw.accept) ? exercise.accept : []
  problems.check(accept.length > 0, `${where}.accept: хотя бы один правильный ответ`)
  accept.forEach((pattern, index) => problems.english(pattern, `${where}.accept[${index}]`))

  // Каждый правильный ответ (со всеми {a|b}) проходит
  for (const answer of accept.flatMap(expand)) {
    problems.check(
      checkAnswer(exercise, written(exercise, answer)).right,
      `${where}: правильный ответ «${answer}» не проходит`,
    )
  }

  // Каждая частая ошибка — неверна и объясняется своим текстом
  const mistakes = Array.isArray(raw.mistakes) ? (raw.mistakes as unknown[]) : []
  problems.check(Array.isArray(raw.mistakes), `${where}.mistakes: список`)
  mistakes.forEach((mistake, index) => {
    const at = `${where}.mistakes[${index}]`
    if (!problems.fields(mistake, ['answer', 'why'], [], at)) {
      return
    }
    const item = mistake as Json
    const answer = String(item.answer)
    problems.english(answer, `${at}.answer`)
    problems.texts(item.why, `${at}.why`)
    // Каждый вариант ошибки (со всеми {a|b}) — неверен и объясняется своим текстом
    for (const variant of expand(answer)) {
      problems.check(
        !accept.some((pattern) => matches(variant, pattern, exercise.exact)),
        `${at}: «${variant}» совпадает с правильным ответом`,
      )
      const verdict = checkAnswer(exercise, written(exercise, variant))
      problems.check(
        !verdict.right && verdict.mistake === ru(item.why),
        `${at}: «${variant}» не показывает своё объяснение`,
      )
    }
  })

  if (exercise.kind === 'gap') {
    problems.english(exercise.text, `${where}.text`)
    problems.check(exercise.text.split('___').length === 2, `${where}.text: ровно один пропуск ___`)
  } else if (exercise.kind === 'translate') {
    // Перевод — с русского: с английским интерфейсом ученик переводил бы английский на английский
    problems.texts(raw.text, `${where}.text`)
    problems.check(keys(raw.text).join() === 'ru', `${where}.text: только {"ru": "…"}`)
  } else if (exercise.kind === 'fix') {
    problems.english(exercise.text, `${where}.text`)
    const wrong = words(exercise.text)[exercise.wrong]
    if (problems.check(Number.isInteger(raw.wrong) && wrong !== undefined, `${where}.wrong`)) {
      problems.check(
        !checkAnswer(exercise, written(exercise, wrong ?? '')).right,
        `${where}: слово «${wrong}» уже правильное — ошибки нет`,
      )
    }
  } else {
    const tiles = Array.isArray(raw.tiles) ? exercise.tiles : []
    problems.check(tiles.length >= 2, `${where}.tiles: хотя бы две плитки`)
    tiles.forEach((tile, index) => problems.english(tile, `${where}.tiles[${index}]`))
    const phrases = [
      ...accept.flatMap(expand),
      ...exercise.mistakes.flatMap((item) => expand(item.answer)),
    ]
    for (const phrase of phrases) {
      problems.check(buildable(phrase, tiles), `${where}: «${phrase}» не собрать из плиток`)
    }
    problems.check(
      !checkAnswer(exercise, { text: tiles.join(' ') }).right,
      `${where}: плитки уже стоят по порядку ответа`,
    )
  }
}

function checkExercise(problems: Problems, raw: unknown, where: string) {
  const kind = isObject(raw) ? raw.kind : undefined
  const known = KINDS.find((item) => item === kind)
  if (!problems.check(known !== undefined, `${where}: kind — ${KINDS.join(', ')}`)) {
    return
  }
  const item = raw as Json
  if (!problems.fields(item, FIELDS[known!], known === 'choice' ? [] : ['forms'], where)) {
    return
  }
  problems.texts(item.prompt, `${where}.prompt`)
  problems.texts(item.explanation, `${where}.explanation`)

  const exercise = toApi(item)
  checkChoice(problems, item, exercise, where)
  checkWritten(problems, item, exercise, where)
}

/** Упражнения шага: список из файла рядом с уроком */
function exercisesOf(problems: Problems, lessonDir: string, step: Json, where: string): unknown[] {
  const name = String(step.file)
  const file = `${lessonDir}/${name}`
  if (
    !problems.check(FILE.test(name), `${where}.file: JSON рядом с уроком — practice/form.json`) ||
    !problems.check(existsSync(path(file)), `${file}: файла нет`)
  ) {
    return []
  }
  const list = json(file)
  return problems.check(Array.isArray(list) && list.length > 0, `${file}: список упражнений`)
    ? (list as unknown[])
    : []
}

function checkTheory(problems: Problems, file: string) {
  if (!problems.check(existsSync(path(file)), `${file}: файла нет`)) {
    return
  }
  for (const block of parseMarkdown(read(file))) {
    if (block.kind === 'examples') {
      for (const example of block.items) {
        problems.english(example.en, `${file}: пример`)
        problems.check(example.translation !== '', `${file}: у «${example.en}» нет перевода`)
      }
    } else if (block.kind === 'mistakes') {
      for (const mistake of block.items) {
        problems.english(mistake.wrong, `${file}: ошибка`)
        problems.english(mistake.right, `${file}: верно`)
        problems.check(mistake.why !== '', `${file}: у «${mistake.wrong}» нет объяснения`)
        problems.check(
          clean(mistake.wrong) !== clean(mistake.right),
          `${file}: «${mistake.wrong}» — неверно и верно совпадают`,
        )
      }
    }
  }
}

function checkLesson(lessonDir: string, isLast: boolean): string[] {
  const problems = new Problems()
  const file = `${lessonDir}/lesson.json`
  const lesson = json(file) as Json
  problems.fields(lesson, ['title', 'steps'], ['kind'], file)
  problems.texts(lesson.title, `${file}: title`)
  problems.check(
    (lesson.kind === 'test') === isLast,
    `${file}: контрольная («kind»: «test») — последний урок модуля и только он`,
  )

  const steps = Array.isArray(lesson.steps) ? (lesson.steps as Json[]) : []
  problems.check(steps.length > 0, `${file}: steps — хотя бы один шаг`)
  const practiceIds: string[] = []

  steps.forEach((step, index) => {
    const where = `${file}: steps[${index}]`
    if (!problems.check(STEP_TYPES.includes(String(step.type)), `${where}: type — ${STEP_TYPES}`)) {
      return
    }
    if (step.type === 'theory') {
      problems.fields(step, ['type', 'text'], [], where)
      if (problems.texts(step.text, `${where}.text`)) {
        Object.values(step.text as Json).forEach((name) =>
          checkTheory(problems, `${lessonDir}/${String(name)}`),
        )
      }
      return
    }
    const quiz = step.type === 'quiz'
    problems.fields(step, quiz ? ['type', 'file'] : ['type', 'id', 'file'], [], where)
    const list = exercisesOf(problems, lessonDir, step, where)
    if (quiz) {
      problems.check(index === steps.length - 1, `${where}: проверка знаний — последний шаг`)
      problems.check(list.length >= 2 && list.length <= 4, `${where}: в проверке 2–4 упражнения`)
    } else {
      problems.check(SLUG.test(String(step.id)) && step.id !== 'quiz', `${where}.id: slug`)
      problems.check(list.length >= 2, `${where}: в практике хотя бы 2 упражнения`)
      practiceIds.push(String(step.id))
    }
    list.forEach((exercise, at) =>
      checkExercise(problems, exercise, `${lessonDir}/${String(step.file)}[${at}]`),
    )
  })

  problems.check(
    steps.filter((step) => step.type === 'quiz').length === 1,
    `${file}: ровно одна проверка знаний`,
  )
  problems.check(
    new Set(practiceIds).size === practiceIds.length,
    `${file}: id практик повторяются`,
  )
  return problems.list
}

const course = json(`${COURSE}/course.json`) as Json
const modules = Array.isArray(course.modules) ? (course.modules as string[]) : []

describe('курс английского (content/english)', () => {
  it('есть в courses.json, course.json полный', () => {
    const problems = new Problems()
    problems.check(
      (json('courses.json') as { courses: string[] }).courses.includes(COURSE),
      'courses.json: нет english',
    )
    problems.fields(course, ['slug', 'title', 'description', 'modules'], [], 'course.json')
    problems.check(course.slug === COURSE, 'course.json: slug — как папка')
    problems.texts(course.title, 'course.json: title')
    problems.texts(course.description, 'course.json: description')
    problems.check(modules.length > 0, 'course.json: modules — хотя бы один')

    expect(problems.list).toEqual([])
  })

  describe.each(modules)('%s', (moduleSlug) => {
    const moduleDir = `${COURSE}/${moduleSlug}`
    const module = json(`${moduleDir}/module.json`) as Json
    const lessons = Array.isArray(module.lessons) ? (module.lessons as string[]) : []

    it('module.json полный, уроки на месте', () => {
      const problems = new Problems()
      problems.check(SLUG.test(moduleSlug), `${moduleDir}: имя папки`)
      problems.fields(module, ['title', 'description', 'lessons'], [], `${moduleDir}/module.json`)
      problems.texts(module.title, `${moduleDir}: title`)
      problems.texts(module.description, `${moduleDir}: description`)
      problems.check(lessons.length > 0, `${moduleDir}: хотя бы один урок`)
      for (const lesson of lessons) {
        problems.check(
          SLUG.test(lesson) && existsSync(path(`${moduleDir}/${lesson}/lesson.json`)),
          `${moduleDir}/${lesson}: нет lesson.json`,
        )
      }

      expect(problems.list).toEqual([])
    })

    it.each(lessons.map((lesson, index) => ({ lesson, last: index === lessons.length - 1 })))(
      '$lesson: структура, теория и каждое упражнение',
      ({ lesson, last }) => {
        expect(checkLesson(`${moduleDir}/${lesson}`, last)).toEqual([])
      },
    )
  })
})
