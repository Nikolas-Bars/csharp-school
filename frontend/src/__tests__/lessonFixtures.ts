import type { Course, Lesson, TaskStep } from '@/types/api'

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
        { slug: '01-hello', title: 'Первая программа', tasks: 2 },
        { slug: '02-errors', title: 'Ошибки — это нормально', tasks: 0 },
      ],
    },
  ],
}

export function apiReply(data: unknown, status = 200) {
  return new Response(JSON.stringify({ status: 'success', message: '', data, errors: {} }), {
    status,
  })
}
