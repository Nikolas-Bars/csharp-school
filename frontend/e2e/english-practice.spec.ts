import { expect, test } from '@playwright/test'

import ru from '../src/i18n/locales/ru.json' with { type: 'json' }
import { englishPractice } from './course'

/**
 * Практика урока английского в настоящем Chromium: упражнение проверяется в браузере, ошибка
 * объясняется, а компилятор C# (Web Worker, .NET) в этом уроке не грузится вовсе.
 */
const practice = englishPractice('01-simple-tenses', '01-present-simple', 'form')

test('практика английского: ошибка с объяснением, верный ответ, без компилятора C#', async ({
  page,
}) => {
  const problems: string[] = []
  page.on('console', (message) => {
    if (message.type() === 'error') {
      problems.push(`console: ${message.text()}`)
    }
  })
  page.on('pageerror', (error) => problems.push(`pageerror: ${error.message}`))
  const framework: string[] = []
  page.on('request', (request) => {
    if (/\/(runner|_framework)\//.test(new URL(request.url()).pathname)) {
      framework.push(request.url())
    }
  })

  const first = practice.exercises[0]
  if (first?.kind !== 'choice' || !('options' in first)) {
    throw new Error('Первое упражнение практики form — выбор варианта: тест на него опирается')
  }
  const wrong = first.options.findIndex((_, index) => index !== first.answer)

  /** Выбрать вариант, как ученик: щелчок по тексту; радиокнопка с этим именем — выбрана */
  async function choose(text: string) {
    await page
      .getByRole('group', { name: ru.exercise.options })
      .getByText(text, { exact: true })
      .click()
    await expect(page.getByRole('radio', { name: text, exact: true })).toBeChecked()
  }

  await page.goto(practice.url)
  await expect(page.getByRole('heading', { level: 1, name: practice.lessonTitle })).toBeVisible()
  await expect(page.getByRole('heading', { level: 2, name: first.prompt.ru })).toBeVisible()
  const total = practice.exercises.length
  await expect(
    page.getByText(ru.exercise.counter.replace('{current}', '1').replace('{total}', `${total}`)),
  ).toBeVisible()

  await test.step('неверный вариант — «Пока не так» и объяснение', async () => {
    await choose(first.options[wrong]!.text)
    await page.getByRole('button', { name: ru.exercise.check }).click()
    await expect(page.getByText(ru.exercise.wrong)).toBeVisible()
  })

  await test.step('верный вариант — «Верно!», дальше — второе упражнение', async () => {
    await choose(first.options[first.answer]!.text)
    await page.getByRole('button', { name: ru.exercise.check }).click()
    await expect(page.getByText(ru.exercise.right)).toBeVisible()
    await page.getByRole('button', { name: ru.exercise.next }).click()
    await expect(
      page.getByText(ru.exercise.counter.replace('{current}', '2').replace('{total}', `${total}`)),
    ).toBeVisible()
  })

  await test.step('компилятор C# не загружался, ошибок в консоли нет', () => {
    expect(page.workers()).toHaveLength(0)
    expect(framework).toEqual([])
    expect(problems).toEqual([])
  })
})
