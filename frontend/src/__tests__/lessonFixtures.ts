import type {
  CourseWord,
  WordCard,
  ChoiceExercise,
  Course,
  CourseSummary,
  FixExercise,
  GapExercise,
  Lesson,
  OrderExercise,
  TaskStep,
  TranslateExercise,
} from '@/types/api'

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
          words: 0,
          steps: ['hello-world', 'quiz'],
        },
        {
          slug: '02-errors',
          title: 'Ошибки — это нормально',
          kind: 'lesson',
          tasks: 0,
          words: 0,
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
          words: 0,
          steps: ['hello-world', 'quiz'],
        },
        {
          slug: '02-module-test',
          title: 'Контрольная: гоблин',
          kind: 'test',
          tasks: 1,
          words: 0,
          steps: ['goblin', 'quiz'],
        },
      ],
    },
    {
      slug: '02-loops',
      title: 'Циклы',
      description: 'while и for',
      lessons: [
        {
          slug: '01-while',
          title: 'Цикл while',
          kind: 'lesson',
          tasks: 0,
          words: 0,
          steps: ['quiz'],
        },
        {
          slug: '02-module-test',
          title: 'Контрольная: подземелье',
          kind: 'test',
          tasks: 0,
          words: 0,
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

/** Упражнения английского — по одному каждого вида */
export const choiceExercise: ChoiceExercise = {
  kind: 'choice',
  prompt: 'Как сказать «Я сейчас читаю»?',
  explanation: 'I → am, к глаголу -ing.',
  options: [
    { text: 'I read now.', why: 'Это Present Simple — про «обычно».' },
    { text: 'I am reading now.', why: null },
    { text: 'I reading now.', why: 'Пропущен am.' },
  ],
  answer: 1,
}

const written = { mistakes: [], hint: 'Подсказка без ответа.', exact: false }

export const gapExercise: GapExercise = {
  ...written,
  kind: 'gap',
  prompt: 'Вставь глагол в нужной форме.',
  explanation: 'she → is, к глаголу -ing.',
  text: 'She ___ (cook) dinner now.',
  accept: ['is cooking'],
  mistakes: [{ answer: 'cooks', why: 'now — сейчас, нужен Continuous.' }],
}

export const orderExercise: OrderExercise = {
  ...written,
  kind: 'order',
  prompt: 'Собери вопрос «Что ты делаешь?»',
  explanation: 'What + are + you + doing.',
  tiles: ['doing', 'you', 'What', 'do', 'are'],
  accept: ['What are you doing'],
  mistakes: [{ answer: 'What do you doing', why: 'do тут лишнее.' }],
}

export const translateExercise: TranslateExercise = {
  ...written,
  kind: 'translate',
  prompt: 'Переведи на английский.',
  explanation: 'Сейчас — Present Continuous.',
  text: 'Я работаю.',
  accept: ['I am working'],
}

export const fixExercise: FixExercise = {
  ...written,
  kind: 'fix',
  prompt: 'Найди ошибку.',
  explanation: 'С she — is.',
  text: 'Look! She are waiting.',
  wrong: 2,
  accept: ['is'],
}

/** Урок английского: теория, практика, проверка знаний из упражнений */
export const englishLesson: Lesson = {
  slug: '01-right-now',
  title: 'Что происходит сейчас',
  module: { slug: '01-present', title: 'Настоящее время' },
  course: { slug: 'english', title: 'Английский' },
  steps: [
    {
      type: 'theory',
      text: '# Сейчас\n\n```example\nLook! It is raining.\nСмотри! Идёт дождь.\n```',
    },
    { type: 'practice', id: 'form', exercises: [choiceExercise, gapExercise] },
    { type: 'quiz', exercises: [orderExercise] },
  ],
}

export const englishCourse: Course = {
  slug: 'english',
  title: 'Английский',
  modules: [
    {
      slug: '01-present',
      title: 'Настоящее время',
      description: 'Сейчас',
      lessons: [
        {
          slug: '01-right-now',
          title: 'Что происходит сейчас',
          kind: 'lesson',
          tasks: 0,
          words: 0,
          steps: ['form', 'quiz'],
        },
      ],
    },
  ],
}

export const wearCard: WordCard = {
  id: 'wear',
  word: 'wear',
  ipa: '/wɛr/',
  pos: 'verb',
  translation: 'носить (одежду)',
  forms: ['wear', 'wore', 'worn'],
  examples: [
    {
      en: 'She is wearing a red jacket.',
      translation: 'На ней красная куртка.',
      why: 'Сейчас — Present Continuous.',
    },
  ],
  compare: '**Не путай с put on.** put on — ==надеть==.',
}

export const momentCard: WordCard = {
  id: 'at-the-moment',
  word: 'at the moment',
  ipa: '/æt ðə ˈmoʊmənt/',
  pos: 'phrase',
  translation: 'сейчас',
  forms: null,
  examples: [
    { en: 'I am busy at the moment.', translation: 'Я сейчас занят.', why: 'Маркер Continuous.' },
  ],
  compare: null,
}

/** Урок английского с карточками слов вторым шагом */
export const wordsLesson: Lesson = {
  ...englishLesson,
  steps: [
    englishLesson.steps[0]!,
    { type: 'words', words: [wearCard, momentCard] },
    ...englishLesson.steps.slice(1),
  ],
}

/** Курс английского, где в уроке есть карточки */
export const wordsCourse: Course = {
  ...englishCourse,
  modules: englishCourse.modules.map((module) => ({
    ...module,
    lessons: module.lessons.map((item) => ({ ...item, words: 2 })),
  })),
}

/** Слова курса для словаря — как отдаёт GET courses/english/words */
export const courseWords: CourseWord[] = [wearCard, momentCard].map((card) => ({
  ...card,
  module: { slug: '01-present', title: 'Настоящее время' },
  lesson: { slug: '01-right-now', title: 'Что происходит сейчас' },
}))

export function apiReply(data: unknown, status = 200) {
  return new Response(JSON.stringify({ status: 'success', message: '', data, errors: {} }), {
    status,
  })
}
