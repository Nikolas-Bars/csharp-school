import type { Exercise } from '@/types/api'

/**
 * Проверка ответа на упражнение английского — одна функция и для ученика в браузере, и для тестов
 * контента (src/__tests__/englishContent.spec.ts): правильные ответы курса проходят, частые ошибки
 * падают со своим объяснением.
 *
 * Не важны: регистр, лишние пробелы, знаки препинания (кроме апострофа), вид апострофа (’ = '),
 * запись времени (6 p.m. = 6pm = 6:00 PM, 8:00 = 8), okay = OK.
 * Краткие и полные формы равны: don't = do not (и dont без апострофа), I'm = I am, can't = cannot =
 * can not, won't = will not. 's = is (и притяжательное Tom's), а has — только перед got, been,
 * better или причастием (has gone, has worked); 'd = would, а had — там же: «She's a dog» не
 * засчитывается за «She has a dog». С `exact` формы различаются — для заданий «напиши краткую
 * форму». Опечатки не прощаются: в грамматике одна буква и есть ошибка (work / works).
 *
 * В правильном ответе {a|b} — любой из вариантов, {a|} — необязательное слово:
 * «I am not watching {TV|television}{ now|}».
 */

/** Ответ ученика: номер варианта, текст или слово с ошибкой и исправление */
export type Answer = { option: number } | { text: string } | { word: number; text: string }

export type Verdict =
  | { right: true }
  | {
      right: false
      /** Объяснение именно этой ошибки (частая ошибка или неверный вариант), иначе null */
      mistake: string | null
      /** «Найди ошибку»: выбрано не то слово */
      wrongWord: boolean
    }

/** Сколько вариантов прочтения ответа берём самое большее: краткие формы умножают их */
const MAX_VARIANTS = 4096

const APOSTROPHES = /[’‘`ʼ´]/g
/** Всё, кроме букв, цифр, апострофа и дефиса внутри слова, — пробел */
const PUNCTUATION = /[^\p{L}\p{N}'\s-]|(?<!\p{L})-|-(?!\p{L})/gu

/** Приводит к виду для сравнения: строчные, без препинания, один пробел между словами */
export function clean(text: string): string {
  return (
    text
      .toLowerCase()
      .replace(APOSTROPHES, "'")
      // Время пишут по-разному: 6 p.m. = 6pm = 6 PM = 6:00 pm, 8:00 = 8
      .replace(/(\d)\s*([ap])\.?\s*m\b\.?/g, '$1 $2m')
      .replace(/(\d):00\b/g, '$1')
      .replace(/\bokay\b/g, 'ok')
      .replace(PUNCTUATION, ' ')
      .replace(/\bcan not\b/g, 'cannot')
      .replace(/\s+/g, ' ')
      .trim()
      .replace(/^'(.*)'$/, '$1') // ответ в кавычках: 'Do'
      .trim()
  )
}

/** Глаголы с краткой формой на n't (don't, isn't…); can't, won't, shan't — особые */
const NEGATABLE = new Set(
  'do does did is are was were have has had could would should must need might'.split(' '),
)

/** Частые неправильные причастия (3-я форма): перед ними 's = has, 'd = had */
const PARTICIPLES = new Set(
  'been gone done seen had made said told bought brought taught thought found left lost met read run sent spent won written given taken eaten known begun broken chosen driven fallen flown forgotten got gotten heard held kept paid put sat slept stood understood swum spoken come become ridden drunk built felt grown shown thrown worn caught fought hidden stolen woken sold cut hit shut set let hurt cost quit spread lent bent meant dealt dreamt learnt burnt lit hung dug stuck struck swung sung rung sunk drawn blown torn sworn bitten beaten forgiven frozen shaken risen fed led fled'.split(
    ' ',
  ),
)

/** Наречия между 's / 'd и причастием: «she's just left», «he's never been» */
const PERFECT_ADVERBS = new Set([
  'just',
  'already',
  'never',
  'ever',
  'still',
  'not',
  'always',
  'recently',
  'only',
  'finally',
  'also',
  'lately',
])

/** После 's / 'd идёт Perfect или had better — тогда это has / had; rest — слова после краткой формы */
function perfectFollows(rest: string[]): boolean {
  const next = rest.find((word) => !PERFECT_ADVERBS.has(word))
  return next !== undefined && (PARTICIPLES.has(next) || next === 'better' || next.endsWith('ed'))
}

/** Слово с краткой формой — все его прочтения, включая само слово; rest — слова после него */
function readings(word: string, rest: string[]): string[] {
  const special: Record<string, string> = {
    "can't": 'cannot',
    "won't": 'will not',
    "shan't": 'shall not',
    "let's": 'let us',
  }
  if (special[word]) {
    return [word, special[word]]
  }
  // n't без апострофа, как часто пишут с телефона: dont, doesnt, isnt (cant и wont — другие слова)
  const bare = /^(.+)nt$/.exec(word)
  if (bare && NEGATABLE.has(bare[1] ?? '')) {
    return [word, `${bare[1]} not`]
  }
  // Окончание бывает и отдельным словом: «'s crying» в пропуске после «The baby»
  const match = /^(.*?)(n't|'m|'re|'ve|'ll|'s|'d)$/.exec(word)
  if (!match) {
    return [word]
  }
  const [, stem = '', ending] = match
  // n't бывает только у настоящих кратких форм: willn't или amn't — не слова, а ошибка
  if (ending === "n't" && !NEGATABLE.has(stem)) {
    return [word]
  }
  const full: Record<string, string[]> = {
    "n't": ['not'],
    "'m": ['am'],
    "'re": ['are'],
    "'ve": ['have'],
    "'ll": ['will'],
    "'s": perfectFollows(rest) ? ['is', 'has'] : ['is'],
    "'d": perfectFollows(rest) ? ['would', 'had'] : ['would'],
  }
  return [
    word,
    ...(full[ending ?? ''] ?? []).map((form) => (stem === '' ? form : `${stem} ${form}`)),
  ]
}

/** Все прочтения фразы: каждое слово с краткой формой — во всех своих вариантах */
export function variants(text: string, exact = false): Set<string> {
  const cleaned = clean(text)
  if (exact || cleaned === '') {
    return new Set([cleaned])
  }
  let results = ['']
  const list = cleaned.split(' ')
  for (const [index, word] of list.entries()) {
    const options = readings(word, list.slice(index + 1))
    const next: string[] = []
    for (const start of results) {
      for (const option of options) {
        if (next.length < MAX_VARIANTS) {
          next.push(start === '' ? option : `${start} ${option}`)
        }
      }
    }
    results = next
  }
  return new Set(results)
}

/** Все правильные ответы шаблона: «I {am|'m} here» → «I am here», «I 'm here» */
export function expand(pattern: string): string[] {
  const group = /\{([^{}]*)\}/.exec(pattern)
  if (!group) {
    return [pattern]
  }
  const before = pattern.slice(0, group.index)
  const after = pattern.slice(group.index + group[0].length)
  return (group[1] ?? '').split('|').flatMap((option) => expand(`${before}${option}${after}`))
}

/** Ответ совпадает с шаблоном правильного ответа (или частой ошибки) */
export function matches(given: string, pattern: string, exact = false): boolean {
  const answer = variants(given, exact)
  return expand(pattern).some((option) => [...variants(option, exact)].some((v) => answer.has(v)))
}

/** Слова фразы «найди ошибку»: номер `wrong` — среди них */
export function words(text: string): string[] {
  return text.split(/\s+/).filter((word) => word !== '')
}

/** Проверяет ответ ученика */
export function checkAnswer(exercise: Exercise, answer: Answer): Verdict {
  if (exercise.kind === 'choice') {
    const option = 'option' in answer ? answer.option : -1
    return option === exercise.answer
      ? { right: true }
      : { right: false, mistake: exercise.options[option]?.why ?? null, wrongWord: false }
  }
  if (exercise.kind === 'fix' && (!('word' in answer) || answer.word !== exercise.wrong)) {
    return { right: false, mistake: null, wrongWord: true }
  }

  const text = 'text' in answer ? answer.text : ''
  if (clean(text) === '') {
    return { right: false, mistake: null, wrongWord: false }
  }
  if (exercise.accept.some((pattern) => matches(text, pattern, exercise.exact))) {
    return { right: true }
  }
  const mistake = exercise.mistakes.find((item) => matches(text, item.answer, exercise.exact))
  return { right: false, mistake: mistake?.why ?? null, wrongWord: false }
}

/** Правильный ответ для показа после второй неудачи: первый из правильных, без {…|…} */
export function shownAnswer(exercise: Exercise): string {
  if (exercise.kind === 'choice') {
    return exercise.options[exercise.answer]?.text ?? ''
  }
  const first = expand(exercise.accept[0] ?? '')[0] ?? ''
  if (exercise.kind === 'fix') {
    const word = words(exercise.text)[exercise.wrong] ?? ''
    return `${word.replace(/[^\p{L}\p{N}']+$/u, '')} → ${first}`
  }
  return first
}
