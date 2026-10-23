import { describe, expect, it } from 'vitest'

import type {
  ChoiceExercise,
  FixExercise,
  GapExercise,
  OrderExercise,
  TranslateExercise,
} from '@/types/api'
import { checkAnswer, clean, expand, matches, shownAnswer, variants, words } from '@/utils/answers'

const written = {
  prompt: 'Задание',
  explanation: 'Почему так',
  hint: 'Подсказка',
  mistakes: [],
  exact: false,
}

describe('clean', () => {
  it('убирает регистр, лишние пробелы и препинание, кроме апострофа', () => {
    expect(clean('  What   are YOU doing?! ')).toBe('what are you doing')
    expect(clean('I am not watching TV, I am working.')).toBe('i am not watching tv i am working')
    expect(clean('«Hello» — "world"')).toBe('hello world')
  })

  it('апостроф любого вида — обычный', () => {
    expect(clean('I’m here')).toBe("i'm here")
    expect(clean('don`t')).toBe("don't")
  })

  it('дефис внутри слова остаётся, тире между словами — нет', () => {
    expect(clean('twenty-one - two')).toBe('twenty-one two')
  })

  it('can not = cannot', () => {
    expect(clean('I can not swim')).toBe('i cannot swim')
  })
})

describe('variants', () => {
  it('краткие формы — во всех прочтениях', () => {
    expect(variants("I'm")).toEqual(new Set(["i'm", 'i am']))
    expect(variants("She's")).toEqual(new Set(["she's", 'she is']))
    expect(variants("She's gone")).toEqual(new Set(["she's gone", 'she is gone', 'she has gone']))
    expect(variants("I'd go")).toEqual(new Set(["i'd go", 'i would go']))
    expect(variants("I'd finished")).toEqual(
      new Set(["i'd finished", 'i would finished', 'i had finished']),
    )
    expect(variants("won't")).toEqual(new Set(["won't", 'will not']))
    expect(variants("can't")).toEqual(new Set(["can't", 'cannot']))
    expect(variants("let's go")).toEqual(new Set(["let's go", 'let us go']))
  })

  it('exact — только как написано', () => {
    expect(variants("I'm", true)).toEqual(new Set(["i'm"]))
  })

  it('много кратких форм не раздувают проверку без предела', () => {
    expect(variants(Array(20).fill("it's").join(' ')).size).toBeLessThanOrEqual(4096)
  })
})

describe('expand', () => {
  it('{a|b} — любой вариант, {a|} — необязательное', () => {
    expect(expand('I am watching {TV|television}{ now|}')).toEqual([
      'I am watching TV now',
      'I am watching TV',
      'I am watching television now',
      'I am watching television',
    ])
    expect(expand('без вариантов')).toEqual(['без вариантов'])
  })
})

describe('matches', () => {
  it('краткие и полные формы равны в обе стороны', () => {
    expect(matches("I don't know", 'I do not know')).toBe(true)
    expect(matches('I do not know', "I don't know")).toBe(true)
    expect(matches("He isn't working, he's sleeping", 'He is not working, he is sleeping')).toBe(
      true,
    )
    expect(matches("I've done it", 'I have done it')).toBe(true)
    expect(matches("We'll see", 'We will see')).toBe(true)
    expect(matches("They're here", 'They are here')).toBe(true)
    expect(matches("I can't swim", 'I can not swim')).toBe(true)
  })

  it("'s = has и 'd = had — только в Perfect и had better", () => {
    expect(matches("She's got a dog", 'She has got a dog')).toBe(true)
    expect(matches("He's been there", 'He has been there')).toBe(true)
    expect(matches("I'd better go", 'I had better go')).toBe(true)
    expect(matches("I'd like tea", 'I would like tea')).toBe(true)
    expect(matches("She's a dog", 'She has a dog')).toBe(false)
    expect(matches("I'd breakfast", 'I had breakfast')).toBe(false)
  })

  it('dont без апострофа и ответ в кавычках', () => {
    expect(matches('I dont know', "I don't know")).toBe(true)
    expect(matches('She doesnt work', 'She does not work')).toBe(true)
    expect(matches("'Do'", 'Do')).toBe(true)
    expect(matches('I cant swim', "I can't swim")).toBe(false)
  })

  it('опечатка или другая форма глагола — не совпадает', () => {
    expect(matches('He work', 'He works')).toBe(false)
    expect(matches('I am read', 'I am reading')).toBe(false)
  })

  it('exact различает краткую и полную форму', () => {
    expect(matches("don't", 'do not', true)).toBe(false)
    expect(matches("Don't.", "don't", true)).toBe(true)
  })
})

describe('checkAnswer', () => {
  const choice: ChoiceExercise = {
    kind: 'choice',
    prompt: 'Как сказать «Я сейчас читаю»?',
    explanation: 'am + -ing',
    options: [
      { text: 'I read now.', why: 'Это Present Simple.' },
      { text: 'I am reading now.', why: null },
    ],
    answer: 1,
  }
  const gap: GapExercise = {
    ...written,
    kind: 'gap',
    text: 'She ___ (cook) dinner at the moment.',
    accept: ['is cooking'],
    mistakes: [{ answer: 'cooks', why: 'at the moment — сейчас.' }],
  }
  const order: OrderExercise = {
    ...written,
    kind: 'order',
    tiles: ['doing', 'you', 'What', 'do', 'are'],
    accept: ['What are you doing'],
    mistakes: [{ answer: 'What do you doing', why: 'do лишнее.' }],
  }
  const translate: TranslateExercise = {
    ...written,
    kind: 'translate',
    text: 'Я не смотрю телевизор.',
    accept: ['I am not watching {TV|television}'],
  }
  const fix: FixExercise = {
    ...written,
    kind: 'fix',
    text: 'Look! She are waiting for you.',
    wrong: 2,
    accept: ['is'],
    mistakes: [{ answer: 'am', why: 'am — только с I.' }],
  }

  it('вариант: верный проходит, у неверного — его объяснение', () => {
    expect(checkAnswer(choice, { option: 1 })).toEqual({ right: true })
    expect(checkAnswer(choice, { option: 0 })).toEqual({
      right: false,
      mistake: 'Это Present Simple.',
      wrongWord: false,
    })
  })

  it('пропуск: правильный ответ, частая ошибка и любой другой неверный', () => {
    expect(checkAnswer(gap, { text: 'is cooking' })).toEqual({ right: true })
    expect(checkAnswer(gap, { text: "She's cooking" })).toEqual({
      right: false,
      mistake: null,
      wrongWord: false,
    })
    expect(checkAnswer(gap, { text: "'s cooking" })).toEqual({ right: true })
    expect(checkAnswer(gap, { text: 'Cooks.' })).toEqual({
      right: false,
      mistake: 'at the moment — сейчас.',
      wrongWord: false,
    })
    expect(checkAnswer(gap, { text: '   ' })).toEqual({
      right: false,
      mistake: null,
      wrongWord: false,
    })
  })

  it('собери фразу и перевод', () => {
    expect(checkAnswer(order, { text: 'What are you doing' })).toEqual({ right: true })
    expect(checkAnswer(order, { text: 'What do you doing' })).toMatchObject({
      mistake: 'do лишнее.',
    })
    expect(checkAnswer(translate, { text: "I'm not watching television." })).toEqual({
      right: true,
    })
  })

  it('найди ошибку: сначала нужное слово, потом исправление', () => {
    expect(checkAnswer(fix, { word: 1, text: 'is' })).toEqual({
      right: false,
      mistake: null,
      wrongWord: true,
    })
    expect(checkAnswer(fix, { text: 'is' })).toMatchObject({ wrongWord: true })
    expect(checkAnswer(fix, { word: 2, text: 'am' })).toMatchObject({ mistake: 'am — только с I.' })
    expect(checkAnswer(fix, { word: 2, text: 'is' })).toEqual({ right: true })
  })

  it('показанный ответ — первый из правильных, у «найди ошибку» — замена слова', () => {
    expect(shownAnswer(choice)).toBe('I am reading now.')
    expect(shownAnswer(translate)).toBe('I am not watching TV')
    expect(shownAnswer({ ...fix, text: 'I am knowing it.', wrong: 2, accept: ['know'] })).toBe(
      'knowing → know',
    )
    expect(shownAnswer({ ...fix, text: 'He go home.', wrong: 1, accept: ['goes'] })).toBe(
      'go → goes',
    )
  })

  it('слова фразы — через пробел, со знаками у слова', () => {
    expect(words(' Look!  She are ')).toEqual(['Look!', 'She', 'are'])
  })
})
