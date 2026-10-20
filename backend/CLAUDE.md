# C# School API

Бэкенд обучающей платформы C# School (`../CLAUDE.md` — общие правила, `../rules/` — подробности).
Laravel 12, PHP 8.3, RoadRunner, MariaDB 11.4, Redis 7, Laravel Sanctum (Bearer-токены).
Архитектура и правила кода — `ARCHITECTURE.md`; нарушение = ошибка `make check`.

## Что где

- `app/Modules/{Module}/` — модули:
  - `User` — ученики (`nickname`, `email`, `locale`, `password`), контракт `UserAccounts`;
    сидер: `student@example.com`, пароль `Password123`;
  - `Auth` — регистрация, вход, выход, текущий пользователь (`api/auth/*`), токены Sanctum;
  - `Course` — курс из `../content` (смонтирован в контейнер только для чтения):
    `make artisan cmd='course:import'` после правки уроков; `api/courses/*`.
  Дальше по плану: `Progress`.
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

## Правила

- Код учеников сервер **никогда** не выполняет (`ARCHITECTURE.md`, «Безопасность»).
- Коммиты и пуши — только после подтверждения пользователя; ветка задачи — `NNN`
  (`../rules/task-branches.md`).
