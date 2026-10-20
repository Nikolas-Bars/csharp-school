import { afterEach, describe, expect, it } from 'vitest'

import { t } from '@/i18n'
import { DEFAULT_LOCALE, setLocale, SUPPORTED_LOCALES, type Locale } from '@/i18n/locale'
import en from '@/i18n/locales/en.json'
import ru from '@/i18n/locales/ru.json'

type Tree = { [key: string]: string | Tree }

function strings(tree: Tree, prefix = ''): Map<string, string> {
  return new Map(
    Object.entries(tree).flatMap(([key, value]) => {
      const path = prefix + key
      return typeof value === 'string' ? [[path, value]] : [...strings(value, `${path}.`)]
    }),
  )
}

/** Набор подстановок {…}: у каждой формы числа свой {n}, поэтому без повторов */
const placeholders = (text: string) =>
  [...new Set([...text.matchAll(/{(\w+)}/g)].map(([, name]) => name))].sort()

const messages: Record<Locale, Tree> = { ru, en }
const base = strings(messages[DEFAULT_LOCALE])

describe.each(SUPPORTED_LOCALES.filter((code) => code !== DEFAULT_LOCALE))('%s.json', (code) => {
  const translated = strings(messages[code])

  it('те же ключи, что в русском', () => {
    expect([...translated.keys()].sort()).toEqual([...base.keys()].sort())
  })

  it('те же подстановки и ни одной пустой строки', () => {
    for (const [key, text] of translated) {
      expect([key, placeholders(text)]).toEqual([key, placeholders(base.get(key) ?? '')])
      expect([key, text.trim()]).not.toEqual([key, ''])
    }
  })
})

describe('t()', () => {
  afterEach(() => setLocale(DEFAULT_LOCALE))

  it('русские формы числа', () => {
    expect([1, 3, 5, 11, 21, 22, 0].map((n) => t('lessons.count', n))).toEqual([
      '1 урок',
      '3 урока',
      '5 уроков',
      '11 уроков',
      '21 урок',
      '22 урока',
      '0 уроков',
    ])
  })

  it('после setLocale тексты сразу на новом языке, и язык запоминается', () => {
    setLocale('en')

    expect(t('lessons.count', 1)).toBe('1 lesson')
    expect(t('lessons.count', 5)).toBe('5 lessons')
    expect(document.documentElement.lang).toBe('en')
    expect(localStorage.getItem('locale')).toBe('en')
  })
})
