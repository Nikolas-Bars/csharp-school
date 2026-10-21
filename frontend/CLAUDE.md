# C# School Web

Vue 3 + Vite + TypeScript SPA обучающей платформы C# School для API в `../backend`
(`../CLAUDE.md` — общие правила, `../rules/` — подробности). Router, Pinia, vue-i18n, Vitest,
oxlint + ESLint, Prettier (без точек с запятой, одинарные кавычки, ширина 100). Настройки
инструментов — стандартные из `create-vue`; наши добавки — в `vite.config.ts` (порт, Docker, i18n).

## Архитектура (строго)

- Компоненты: `<script setup lang="ts">`, типизированные `defineProps` / `defineEmits` /
  `defineModel`, `scoped` стили.
- Папки и их роли:
  - `src/api/http.ts` — единственное место, где фронт ходит в сеть (`http.get/post/put/delete`).
    Бэкенд всегда отвечает `{status, message, data, errors}`: успех разворачивается до `data`,
    остальное — `ApiError(message, status, errors)`, текст уже на языке ученика. Рядом — **один
    файл на модуль бэкенда** (`api/auth.ts`, `api/courses.ts`, `api/progress.ts`).
  - `src/types/` — типы ответов API, зеркалят ресурсы бэкенда (`types/api.ts`).
  - `src/stores/` — Pinia; стор — единственный владелец состояния своей области
    (`auth` — вход: гость — нормальное состояние, учиться можно без аккаунта; `progress` —
    у гостя в `localStorage`, у вошедшего — на сервере, а не дошедшие шаги ждут в `localStorage`;
    гостевой прогресс при входе переносится в аккаунт; повторная загрузка того же курса не шлёт
    запросов заново).
  - `src/composables/` — переиспользуемая реактивная логика (`useXxx`).
  - `src/components/ui/Base*.vue` — базовые компоненты без бизнес-логики (`BaseIcon`,
    `BaseButton`, `BaseField` — поле с подписью, подсказкой и ошибкой, связанными для диктора).
  - `src/components/layout/` — оболочка приложения (`AppHeader`, `ThemeToggle`).
  - `src/components/{feature}/` — компоненты фичи. `lesson/`: `LessonTask` (условие, подсказки,
    редактор, проверки через runner; на телефоне — вкладки), `LessonQuiz`, `MarkdownView` (текст
    урока без v-html), `CodeBlock`, `CodeEditor` (CodeMirror 6, грузится лениво, ~105 КБ gzip).
  - `src/views/*View.vue` — страницы маршрутов, лениво загружаются в роутере.
  - `src/runner/` — мост к runner (компилятор C# в Web Worker), без компонентов Vue:
    `protocol.ts` (зеркало протокола runner), `runner.worker.ts` (загружает .NET из
    `public/runner`), `client.ts` (очередь запросов, таймаут бесконечного цикла, перезапуск
    воркера), `explain.ts` (ошибки компилятора и программы простыми словами — `compiler.*`,
    `runtime.*` в локалях). Компоненты берут его через `composables/useRunner.ts`.
  - `src/utils/` — чистые функции (`safeRedirect` — возврат после входа только внутри сайта;
    `queryText` — параметр адреса строкой;
    `markdown` — разбор Markdown урока в структуру; `csharpTokens` — подсветка C# для теории
    и редактора).
- Вход: токен Sanctum в `localStorage` (`api/tokenStorage.ts`), `http.ts` шлёт его в
  `Authorization`; 401 на любом запросе — токен забывается (`onUnauthorized`), стор выходит.
  Страницы только для гостя — `meta.guestOnly` в роутере. Забыли пароль: `/forgot-password` →
  письмо → `/reset-password?token=…&email=…` (адрес собирает бэкенд) → `/login?reset=done`.
- Курс: `/courses/:course` — карта, `/courses/:course/:module/:lesson?step=N` — урок, шаг в адресе.
  Код задачи — черновиком в `localStorage` (`useDraft`); без черновика — последний зачтённый код
  из прогресса (на любом устройстве).
- Зависимости только вниз: `views → components → composables/stores → api → http`.
  Компонент не вызывает `fetch` и `api/*` напрямую. Фича не импортирует внутренности другой фичи.
- Язык интерфейса: `src/i18n/locale.ts` (`SUPPORTED_LOCALES` — подмножество
  `app.supported_locales` бэкенда). Все строки — в `src/i18n/locales/{ru,en}.json`, ключи
  типизированы (`t('home.title')`, опечатка — ошибка type-check). Запросы уходят с
  `Accept-Language`.
- Нет секретов во фронте: всё `VITE_*` попадает в публичный бандл.
- Тяжёлое (компилятор, редактор кода) — лениво (`import()`) и в Web Worker.
- Новый компонент, стор или чистая функция — сразу с тестом в `src/__tests__`.

## Стили и темы

Только через токены, без цветов литералами в компонентах. Подробно — `../rules/frontend-styles.md`.

- `src/assets/tokens.css` — общие для всех тем: шрифты, шкалы, отступы, радиусы, `--color-code-*`.
- `src/assets/fonts.css` — `@font-face` шрифтов, отдаёт сам сайт: файлы из пакетов Fontsource
  (`@fontsource-variable/inter`, `@fontsource/unbounded`, `@fontsource/jetbrains-mono`), только
  нужные толщины и подмножества latin + cyrillic. Inter предзагружается в `index.html`.
  `src/__tests__/fonts.spec.ts` сверяет их и падает на шрифты со сторонних доменов.
- `src/assets/themes/<id>.css` — по файлу на тему; `light` — основная (с макета), `dark` — от неё.
- `src/theme/themes.ts` — реестр тем; `useThemeStore` — выбор (как в системе / светлая / тёмная),
  ставит `data-theme` на `<html>`; скрипт в `index.html` — то же до первой отрисовки.
- `src/__tests__/themes.spec.ts` сверяет реестр с файлами тем, `main.css`, переводами и скриптом
  `index.html` и падает на `#…`/`rgb(…)` в `<style>` компонентов.

## Runner

`public/runner/` — сборка `runner` (`make build` там), в git не попадает.
Служебная страница проверки — `/dev/runner`, есть только при разработке (`import.meta.env.DEV`).

## Команды

`make help` — список. `make start` (http://localhost:5390), `make check` (lint + type-check +
test), `make install p=пакет`, `make build`.

Проверка в настоящем браузере — `e2e/` (Playwright, только Chromium; `npm run test:e2e`,
`E2E_BASE_URL` — адрес сайта): урок, компилятор в Web Worker, ошибки по-русски, таймаут, CSP.
Гоняется против production-стека: `CHECK_E2E=1 make prod-check` из корня (`../deploy/README.md`),
в CI — workflow `deploy` (отчёт упавшего теста — артефакт `e2e-results`). Тексты для поиска — из
локалей, задача и эталон — из `../content`. Vitest папку `e2e/` не берёт.

`vite` не ниже 8.3.3: в 8.3.0–8.3.2 уязвимость launch-editor (GHSA-698v-4r3x-3vcv и др.), на ней
падал `npm audit --omit=dev --audit-level=high`.

В Docker на macOS Vite иногда не видит изменение файла; если код устарел — `make restart`.
