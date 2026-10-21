import { expect, test, type Page } from '@playwright/test'

import ru from '../src/i18n/locales/ru.json' with { type: 'json' }
import { courseTask } from './course'

/**
 * Задача урока в настоящем Chromium — как у ученика: компилятор C# (.NET WebAssembly) грузится
 * в Web Worker, код проверяется в браузере. Тесты runner на обычном .NET этого не видят:
 * однопоточный WebAssembly, загрузка из /runner, CSP production-сервера, перезапуск воркера.
 * Тексты — из локалей: тест падает, если ученик увидит не то сообщение.
 */
const task = courseTask('01-first-programs', '01-hello', 'hello-world')

const DIVIDE_BY_ZERO = 'using System;\n\nint zero = 0;\nConsole.WriteLine(10 / zero);\n'
const INFINITE_LOOP = 'using System;\n\nwhile (true)\n{\n}\n'

/**
 * Ответы, которые должны быть успешными: файлы SPA и runner, курс. Остальной API не трогаем —
 * например, законный 401 для гостя
 */
const WATCHED = /^\/(assets|runner)\/|^\/api\/courses\//

/**
 * Всё, что пошло не так в браузере: ошибки консоли (в том числе нарушения CSP) и необработанные
 * исключения — у страницы и у Web Worker (Chromium передаёт их странице), ответы 4xx/5xx из WATCHED
 */
function collectProblems(page: Page): string[] {
  const problems: string[] = []
  page.on('console', (message) => {
    // «Failed to load resource» — ответ 4xx/5xx: его проверяет обработчик response ниже
    if (message.type() === 'error' && !message.text().startsWith('Failed to load resource')) {
      problems.push(`console: ${message.text()}`)
    }
  })
  page.on('pageerror', (error) => problems.push(`pageerror: ${error.message}`))
  page.on('response', (response) => {
    if (response.status() >= 400 && WATCHED.test(new URL(response.url()).pathname)) {
      problems.push(`HTTP ${response.status()}: ${response.url()}`)
    }
  })
  return problems
}

test('задача урока: решение, ошибки компиляции и программы, бесконечный цикл', async ({ page }) => {
  const problems = collectProblems(page)
  const results = page.locator('section[aria-live]').filter({ hasText: ru.task.checks })

  async function check(code: string) {
    await page.getByRole('textbox', { name: ru.task.editor }).fill(code)
    await page.getByRole('button', { name: ru.task.run }).click()
  }

  /** Текст в проверках. Компилятор упал — сразу ошибка с тем, что видел браузер, без ожидания */
  async function expectResult(...texts: string[]) {
    const crashed = results.getByText(ru.runner.crashed)
    await expect(results.getByText(texts[0]!).or(crashed)).toBeVisible()
    expect(await crashed.isVisible(), `${ru.runner.crashed}\n${problems.join('\n')}`).toBe(false)
    for (const text of texts) {
      await expect(results.getByText(text)).toBeVisible()
    }
  }

  // Компилятор начинает грузиться сразу, как открылась задача, — ждём его с самого начала
  const dotnet = page.waitForResponse(/\/_framework\/dotnet(\.[\w-]+)?\.js(\?|$)/)

  await test.step('страница урока открывается на шаге с задачей', async () => {
    await page.goto(task.url)
    await expect(page.getByRole('heading', { level: 1, name: task.lessonTitle })).toBeVisible()
    await expect(page.getByRole('heading', { level: 2, name: task.title })).toBeVisible()
    await expect(page.getByRole('textbox', { name: ru.task.editor })).toBeVisible()
  })

  await test.step('компилятор загружается в Web Worker, эталон проходит все проверки', async () => {
    await check(task.code('solution.cs'))
    expect((await dotnet).ok()).toBe(true)
    expect(page.workers()).toHaveLength(1)

    await expectResult(ru.task.allPassed, ru.task.compiles)
    await expect(page.getByRole('button', { name: ru.task.next })).toBeVisible()
  })

  await test.step('ошибка компиляции — по-русски', async () => {
    await check(task.code('wrong-no-semicolon.cs'))
    await expectResult(ru.task.compileErrors, ru.compiler.CS1002)
  })

  await test.step('исключение в программе объясняется', async () => {
    await check(DIVIDE_BY_ZERO)
    await expectResult(ru.task.crash, ru.runtime.DivideByZeroException)
  })

  await test.step('бесконечный цикл останавливается по таймауту', async () => {
    await check(INFINITE_LOOP)
    await expectResult(ru.runner.timeout)
  })

  await test.step('после таймаута компилятор перезапускается и снова работает', async () => {
    await check(task.code('solution.cs'))
    await expectResult(ru.task.allPassed)
  })

  await test.step('в консоли нет ошибок и нарушений CSP, все запросы успешны', () => {
    expect(problems).toEqual([])
  })
})
