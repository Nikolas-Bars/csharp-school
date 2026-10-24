import { expect, test } from '@playwright/test'

import ru from '../src/i18n/locales/ru.json' with { type: 'json' }
import { englishWords } from './course'

/**
 * Карточки слов и словарь гостя в настоящем Chromium: карточки урока по одной, после последней
 * слова — в «Моём словаре» (в браузере), повторение меняет расписание.
 */
const lesson = englishWords('01-simple-tenses', '01-present-simple')

test('карточки слов → мой словарь → повторение', async ({ page }) => {
  const problems: string[] = []
  page.on('console', (message) => {
    if (message.type() === 'error') {
      problems.push(`console: ${message.text()}`)
    }
  })
  page.on('pageerror', (error) => problems.push(`pageerror: ${error.message}`))

  const [first, second] = lesson.words
  if (!first || !second) {
    throw new Error('В уроке хотя бы две карточки: тест на них опирается')
  }
  const total = lesson.words.length
  const counter = (current: number) =>
    ru.words.step.counter.replace('{current}', `${current}`).replace('{total}', `${total}`)

  await test.step('карточки по одной: слово, перевод, «следующее»', async () => {
    await page.goto(lesson.url)
    await expect(
      page.getByRole('heading', { level: 2, name: first.word, exact: true }),
    ).toBeVisible()
    await expect(page.getByText(first.translation.ru, { exact: true })).toBeVisible()
    await expect(page.getByText(counter(1))).toBeVisible()
    await page.getByRole('button', { name: ru.words.step.next }).click()
    await expect(
      page.getByRole('heading', { level: 2, name: second.word, exact: true }),
    ).toBeVisible()
  })

  await test.step('после последней карточки — урок идёт дальше, слова в словаре', async () => {
    for (let card = 2; card < total; card++) {
      await page.getByRole('button', { name: ru.words.step.next }).click()
    }
    await page.getByRole('button', { name: ru.words.step.done }).click()
    await expect(page).toHaveURL(/step=3/)

    await page.goto('/courses/english/words')
    await expect(
      page.getByRole('heading', { level: 1, name: ru.words.dictionary.title }),
    ).toBeVisible()
    await expect(page.getByText(first.word, { exact: true })).toBeVisible()
  })

  await test.step('повторение: «Показать перевод» → «Помню», слово уходит на потом', async () => {
    await page.goto('/courses/english/words/review')
    await expect(
      page.getByRole('heading', { level: 2, name: first.word, exact: true }),
    ).toBeVisible()
    await page.getByRole('button', { name: ru.words.review.reveal }).click()
    await expect(page.getByText(first.translation.ru, { exact: true })).toBeVisible()
    await page.getByRole('button', { name: ru.words.review.knew, exact: true }).click()

    const saved = await page.evaluate(() => localStorage.getItem('vocabulary:english'))
    const entries = JSON.parse(saved ?? '{}') as Record<string, { box: number }>
    expect(entries[first.id]?.box).toBe(1)
  })

  await test.step('ошибок в консоли нет', () => {
    expect(problems).toEqual([])
  })
})
