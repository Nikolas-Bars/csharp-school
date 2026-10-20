# C# School Web

Vue 3 + Vite + TypeScript SPA обучающей платформы C# School для API в `../csharp-school-backend`
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
  - `src/stores/` — Pinia; стор — единственный владелец состояния своей области.
  - `src/composables/` — переиспользуемая реактивная логика (`useXxx`).
  - `src/components/ui/Base*.vue` — базовые компоненты без бизнес-логики (`BaseIcon`).
  - `src/components/layout/` — оболочка приложения (`AppHeader`, `ThemeToggle`).
  - `src/components/{feature}/` — компоненты фичи (`lesson/`, `course/`).
  - `src/views/*View.vue` — страницы маршрутов, лениво загружаются в роутере.
  - `src/runner/` — мост к runner (компилятор C# в Web Worker), без компонентов Vue:
    `protocol.ts` (зеркало протокола runner), `runner.worker.ts` (загружает .NET из
    `public/runner`), `client.ts` (очередь запросов, таймаут бесконечного цикла, перезапуск
    воркера), `explain.ts` (ошибки компилятора и программы простыми словами — `compiler.*`,
    `runtime.*` в локалях). Компоненты берут его через `composables/useRunner.ts`.
  - `src/utils/` — чистые функции.
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
- `src/assets/themes/<id>.css` — по файлу на тему; `light` — основная (с макета), `dark` — от неё.
- `src/theme/themes.ts` — реестр тем; `useThemeStore` — выбор (как в системе / светлая / тёмная),
  ставит `data-theme` на `<html>`; скрипт в `index.html` — то же до первой отрисовки.
- `src/__tests__/themes.spec.ts` сверяет реестр с файлами тем, `main.css`, переводами и скриптом
  `index.html` и падает на `#…`/`rgb(…)` в `<style>` компонентов.

## Runner

`public/runner/` — сборка `csharp-school-runner` (`make build` там), в git не попадает.
Служебная страница проверки — `/dev/runner`, есть только при разработке (`import.meta.env.DEV`).

## Команды

`make help` — список. `make start` (http://localhost:5390), `make check` (lint + type-check +
test), `make install p=пакет`, `make build`.

В Docker на macOS Vite иногда не видит изменение файла; если код устарел — `make restart`.
