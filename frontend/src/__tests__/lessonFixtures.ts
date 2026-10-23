import type { Course, CourseSummary, Lesson, TaskStep } from '@/types/api'

/** Курс и урок для тестов — в форме ответа API */
export const helloTask: TaskStep = {
  type: 'task',
  id: 'hello-world',
  title: 'Привет, мир!',
  statement: 'Напиши программу, которая выводит `Привет, мир!`',
  starter: 'using System;\n',
  checks: [
    { kind: 'output', expected: 'Привет, мир!' },
    { kind: 'call', method: 'System.Console.WriteLine' },
  ],
  messages: [
    'Программа должна напечатать ровно «Привет, мир!».',
    'Для вывода используй Console.WriteLine.',
  ],
  hints: ['Намёк', 'Подробнее', 'Почти решение'],
}

export const lesson: Lesson = {
  slug: '01-hello',
  title: 'Первая программа',
  module: { slug: '01-first-programs', title: 'Первые программы' },
  course: { slug: 'csharp', title: 'C# с нуля' },
  steps: [
    { type: 'theory', text: '# Что такое программа\n\nСписок команд.' },
    helloTask,
    {
      type: 'quiz',
      questions: [
        {
          question: 'Чем заканчивается команда?',
          options: [';', '.'],
          answer: 0,
          explanation: 'Точкой с запятой.',
        },
      ],
    },
  ],
}

export const course: Course = {
  slug: 'csharp',
  title: 'C# с нуля',
  modules: [
    {
      slug: '01-first-programs',
      title: 'Первые программы',
      description: 'Вывод, переменные',
      lessons: [
        {
          slug: '01-hello',
          title: 'Первая программа',
          kind: 'lesson',
          tasks: 1,
          steps: ['hello-world', 'quiz'],
        },
        {
          slug: '02-errors',
          title: 'Ошибки — это нормально',
          kind: 'lesson',
          tasks: 0,
          steps: ['quiz'],
        },
      ],
    },
  ],
}

/** Курс из двух модулей, каждый заканчивается контрольной */
export const twoModules: Course = {
  slug: 'csharp',
  title: 'C# с нуля',
  modules: [
    {
      slug: '01-first-programs',
      title: 'Первые программы',
      description: 'Вывод, переменные',
      lessons: [
        {
          slug: '01-hello',
          title: 'Первая программа',
          kind: 'lesson',
          tasks: 1,
          steps: ['hello-world', 'quiz'],
        },
        {
          slug: '02-module-test',
          title: 'Контрольная: гоблин',
          kind: 'test',
          tasks: 1,
          steps: ['goblin', 'quiz'],
        },
      ],
    },
    {
      slug: '02-loops',
      title: 'Циклы',
      description: 'while и for',
      lessons: [
        { slug: '01-while', title: 'Цикл while', kind: 'lesson', tasks: 0, steps: ['quiz'] },
        {
          slug: '02-module-test',
          title: 'Контрольная: подземелье',
          kind: 'test',
          tasks: 0,
          steps: ['quiz'],
        },
      ],
    },
  ],
}

/** Контрольная первого модуля twoModules: задача и вопросы */
export const moduleTest: Lesson = {
  slug: '02-module-test',
  title: 'Контрольная: гоблин',
  module: { slug: '01-first-programs', title: 'Первые программы' },
  course: { slug: 'csharp', title: 'C# с нуля' },
  steps: [{ ...helloTask, id: 'goblin', title: 'Бой с гоблином' }, lesson.steps[2]!],
}

/** Курсы на главной — как CourseSummaryResource */
export const csharpSummary: CourseSummary = {
  slug: 'csharp',
  title: 'C# с нуля до первой игры',
  description: 'Настоящий компилятор прямо в браузере.',
  modules: 5,
  lessons: 57,
  start: { module: '01-first-programs', lesson: '01-hello' },
}

export const englishSummary: CourseSummary = {
  slug: 'english',
  title: 'Английский: все времена',
  description: 'Времена простыми словами.',
  modules: 1,
  lessons: 1,
  start: { module: '01-simple-tenses', lesson: '01-present-simple' },
}

export function apiReply(data: unknown, status = 200) {
  return new Response(JSON.stringify({ status: 'success', message: '', data, errors: {} }), {
    status,
  })
}
