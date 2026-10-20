# C# School API

Бэкенд обучающей платформы C# School (`../CLAUDE.md` — общие правила, `../rules/` — подробности).
Laravel 12, PHP 8.3, RoadRunner, MariaDB 11.4, Redis 7, Laravel Sanctum (Bearer-токены).
Архитектура и правила кода — `ARCHITECTURE.md`; нарушение = ошибка `make check`.

## Что где

- `app/Modules/{Module}/` — модули:
  - `User` — ученики (`nickname`, `email`, `locale`, `password`), контракт `UserAccounts`;
    сидер: `student@example.com`, пароль `Password123`;
  - `Auth` — регистрация, вход, выход, текущий пользователь (`api/auth/*`), токены Sanctum;
    сброс пароля по почте (при разработке `MAIL_MAILER=log`: письмо со ссылкой — в `make logs`;
    ссылка ведёт на фронт — `FRONTEND_URL`);
  - `Course` — курс из `../content` (смонтирован в контейнер только для чтения):
    `make artisan cmd='course:import'` после правки уроков; `api/courses/*`;
  - `Progress` — пройденные шаги и код решений (`api/progress/*`, только вошедшим).
- `app/` вне модулей — общее ядро: `ApiResponder`, `Translator`, `ApiException`, `BaseRepository`,
  `ModuleServiceProvider`, middleware `SetLocale` и `ForceJsonResponse`.
- `bootstrap/app.php` — ответы на ошибки в общем формате.
- `config/` — только отличия от настроек Laravel по умолчанию.
- `tests/PHPStanRules/` — правила PHPStan проекта, их тесты и примеры (`Fixtures`).
- `tests/Unit`, `tests/Feature` — тесты ядра; тесты модуля — в `{Module}/Tests`.

## Команды

`make help` — список. Главное: `make start` (первый запуск), `make up` / `make down`,
`make check` (php-cs-fixer + PHPStan level 10 + PHPUnit), `make test`, `make lint` (исправить
стиль), `make shell`, `make artisan cmd='route:list'`, `make composer cmd='require vendor/package'`
(после изменения зависимостей — `make rebuild`: `vendor` живёт в образе).

API: http://localhost:8290/api. Тесты идут на SQLite в памяти (`phpunit.xml`).

Документация API — `openapi.json` (OpenAPI 3.1, из атрибутов `#[OA\…]`, `ARCHITECTURE.md`).
После изменения эндпоинта, формы или ресурса: `make composer cmd=openapi`, иначе `make check`
упадёт. Посмотреть: открыть файл в IDE с поддержкой OpenAPI или Swagger UI локально —
`docker run --rm -p 8291:8080 -e SWAGGER_JSON=/openapi.json -v "$PWD/openapi.json:/openapi.json:ro" swaggerapi/swagger-ui:v5.17.14`
→ http://localhost:8291 (для «Try it out» — `http://localhost:8291` в `CORS_ALLOWED_ORIGINS`).
В production страницы документации нет.

Если git пересоздал папку `../content` (rebase, переключение веток, где её нет), контейнер
видит её пустой («course.json: файла нет») — пересоздать: `docker compose up -d --force-recreate php`.

## Правила

- Код учеников сервер **никогда** не выполняет (`ARCHITECTURE.md`, «Безопасность»).
- Коммиты и пуши — только после подтверждения пользователя; ветка задачи — `NNN`
  (`../rules/task-branches.md`).
