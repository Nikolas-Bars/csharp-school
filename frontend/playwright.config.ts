import { defineConfig, devices } from '@playwright/test'

/**
 * Проверка в настоящем браузере (e2e): урок, компилятор C# в Web Worker, таймаут, ошибки.
 * Гоняется против собранного production-стека (Caddy с CSP и сжатием, API, курс из content/):
 * `CHECK_E2E=1 deploy/check.sh` поднимает стек и запускает эти тесты в контейнере Playwright.
 * E2E_BASE_URL — адрес сайта; без него тесты не запускаются.
 */
const baseURL = process.env.E2E_BASE_URL
if (!baseURL) {
  throw new Error('E2E_BASE_URL не задан: адрес сайта, например http://localhost (deploy/check.sh)')
}

export default defineConfig({
  testDir: './e2e',
  // Каждый тест загружает .NET (~30 МБ) заново — по одному, чтобы не мешать друг другу
  workers: 1,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  // Весь сценарий: две загрузки компилятора (вторая — после таймаута) и таймаут цикла (5 с);
  // с запасом на медленный раннер CI
  timeout: 180_000,
  expect: { timeout: 30_000 },
  reporter: process.env.CI ? [['list'], ['github']] : 'list',
  // deploy/check.sh пишет отчёты внутри контейнера и копирует их наружу
  outputDir: process.env.E2E_OUTPUT_DIR ?? 'test-results',
  use: {
    baseURL,
    locale: 'ru-RU',
    trace: 'retain-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
})
