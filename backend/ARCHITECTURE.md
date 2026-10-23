# Архитектура бэкенда

Модульный монолит: один бэкенд, но каждый модуль можно вынести в отдельный сервис, не
переписывая остальные. Внутри модуля — слои Action → Task → Repository. Общие правила проекта —
`../rules/architecture.md`.

Правила ниже — не пожелания. У каждого указано, что его проверяет: **[PHPStan]** — правила
`tests/PHPStanRules` (у каждого свой тест), **[форматтер]** — php-cs-fixer, **[тест]** — PHPUnit,
**[ревью]** — только ревью (`../rules/review.md`). Всё, кроме ревью, входит в `make check`.

## Структура модуля (`app/Modules/{Module}/`)

```
app/Modules/Course/
├── Actions/          # Сценарии: ShowLessonAction, метод run()
├── Tasks/            # Одна операция: FindLessonTask, метод run()
├── Repositories/     # Запросы к БД: LessonRepository extends BaseRepository
├── DTO/              # Данные между слоями: LessonDTO extends Spatie\LaravelData\Data
├── Models/           # Eloquent-модели
├── Enums/            # enum
├── Contracts/        # Что модуль отдаёт другим: интерфейсы и их DTO
├── Services/         # Реализации своих контрактов: UserAccountsService, собирают Tasks
├── Events/           # Доменные события: только ID и скаляры
├── Exceptions/       # LessonLockedException extends ApiException
├── Http/
│   ├── Controllers/  # Один контроллер — один эндпоинт: ShowLessonController::__invoke
│   ├── Requests/     # Валидация: ShowLessonRequest extends FormRequest
│   └── Resources/    # Ответ: LessonResource extends JsonResource
├── Console/Commands/ # Artisan-команды модуля: ImportCourseCommand (регистрируются в провайдере)
├── Database/         # Migrations/ (подключаются сами), Factories/, Seeders/
├── Providers/        # CourseServiceProvider extends ModuleServiceProvider — подключается сам
└── Tests/            # Feature/ — тест на каждый контроллер, Unit/
```

- **[PHPStan]** Имя модуля — в единственном числе (`Course`, не `Courses`); других папок нет;
  класс в папке слоя носит его суффикс (`Actions/*Action`, `Tasks/*Task`, `DTO/*DTO`,
  `Http/Controllers/*Controller`, `Services/*Service`…) и наследует его базу (`Repositories` — `BaseRepository`,
  `DTO` — `Data`, `Exceptions` — `ApiException`, `Providers` — `ModuleServiceProvider`…);
  в `Enums` — только enum, в `Contracts` — только интерфейсы и DTO.
- Общее для всех модулей — в `app/` вне модулей: `ApiResponder`, `Translator`, `ApiException`,
  `BaseRepository`, `ModuleServiceProvider`, middleware. Своих модулей это ядро не знает.

Модули:

- `User` — ученики (ник, почта, язык, пароль). Отдаёт контракт `UserAccounts`
  (регистрация, поиск по почте и паролю, по почте, по ID, смена пароля) с `UserDTO`; модель
  наружу не выходит.
- `Auth` — токены входа (таблица Sanctum, пользователь — «user» + ID) и эндпоинты
  `auth/register`, `auth/login`, `auth/logout`, `auth/me`; ограничение частоты — в
  `AuthServiceProvider`. Пользователей берёт только через `UserAccounts`.
  Сброс пароля: `auth/forgot-password` → письмо (встроенное уведомление Laravel `ResetPassword`,
  текст — `lang/*/mail.php`, язык — ученика, ссылка — `app.frontend_url` + `/reset-password?token=…&email=…`)
  → `auth/reset-password`. Таблица `password_reset_tokens` (формат Laravel, токен — SHA-256,
  срок — `auth.passwords.users.expire`, ссылка одноразовая). Ответ на запрос письма одинаковый для
  любой почты, а поиск аккаунта и отправка идут после ответа (`defer`) — ни по тексту, ни по времени
  не понять, есть ли аккаунт. Лимиты — по адресу и по почте. После сброса все токены входа ученика
  удаляются. Исключение из «ссылка на чужое — ID» (согласовано): строка сброса привязана к почте, как
  в формате Laravel — с ним работают брокер паролей и `auth:clear-resets`.
- `Course` — курсы → модули курса → уроки (шаги урока — JSON). Источник — файлы `../content`
  (формат — `content/README.md`): `courses.json` — папки курсов по порядку, в каждой свой
  `course.json`. В базу их загружает `course:import` (и сидер) — все курсы одной транзакцией;
  курс, убранный из `courses.json`, не удаляется (на его уроки ссылается прогресс). API только для
  чтения и открыт гостям: `courses` (список для главной: описание, размер, первый урок),
  `courses/{course}` (карта), `courses/{course}/modules/{module}/lessons/{lesson}`.
  Эталоны и неверные решения в базу и API не попадают. Отдаёт контракт `LessonDirectory`
  (уроки курса и их засчитываемые шаги — id задач и `quiz`, `Lesson::countedSteps()`); список
  лежит в кэше, `course:import` его сбрасывает. Импорт требует в каждом уроке ровно одну проверку
  знаний и разные id задач — иначе урок нельзя пройти. Вид урока (`kind`, `Enums/LessonKind`):
  `lesson` или `test` — контрольная модуля; карта курса отдаёт его, фронт по нему отмечает
  контрольную и «модуль пройден». Импорт требует: в курсе есть модули, в модуле — уроки,
  контрольная в модуле одна и последняя.
- `Progress` — пройденные шаги ученика и последний зачтённый код задачи (`completed_steps`; урок —
  ID без внешнего ключа). Уроки берёт через `LessonDirectory`. `progress/courses/{course}` (карта),
  `…/modules/{module}/lessons/{lesson}` (шаги и код), `POST …/steps` — один шаг или прогресс гостя
  пачкой (`guest: true` — дополняет аккаунт, сохранённый код не заменяет). Шаг засчитывается один
  раз, шаг без кода код не стирает, частота ограничена (запись и чтение — отдельно), неизвестные
  уроки и шаги пропускаются.

## Поток запроса

```
Request (валидация) → Controller → DTO → Action::run() → Task::run() → Repository → Model
                                                                                      ↓
                                     JSON ← ApiResponder ← Resource ←─────────────────┘
```

- **Controller** — тонкий: берёт DTO из Request, вызывает один Action, отвечает
  `ApiResponder::ok(LessonResource::make($lesson), Translator::get('messages.course.lesson'))`.
  Маршрут — атрибутом: `#[Get('courses/{course}/lessons')]`; префикс `api` и группа `api`
  добавляются сами (`config/route-attributes.php`).
  - **[PHPStan]** Публичный метод только `__invoke`; есть `Tests/Feature/{Контроллер}Test.php`;
    не вызывает Task и Repository.
  - **[ревью]** Вызывает ровно один Action.
- **Action** — сценарий целиком: собирает Tasks, бросает бизнес-исключения, открывает
  `DB::transaction`, если шагов несколько. **[PHPStan]** Action не вызывает другой Action:
  общий шаг выносится в Task.
- **Task** — одна операция (`CreateUserTask`, `FindLessonTask`). **[PHPStan]** Не вызывает Action.
- **Repository** — `final class LessonRepository extends BaseRepository` (`@extends
  BaseRepository<Lesson>`). **[PHPStan]** Единственное место с запросами к БД (кроме самой модели —
  связи и scope — и `Database/`); вызывается только из Action, Task и другого репозитория.

## Ответы и ошибки

- Формат всегда один: `{ status, message, data, errors }` через `App\Services\ApiResponder`.
  `message` — всегда переведённый текст. Списки — `ApiResponder::page()`: курсорная пагинация,
  `{ items, next_cursor }`, без `offset`. Курсор из запроса Laravel сам не читает (отключено в
  `AppServiceProvider`: на подделанном он падал с 500) — репозиторий получает строку и разбирает её
  `BaseRepository::cursor($cursor, ['position', 'id'])`: не те колонки или кривой — первая страница.
- Бизнес-ошибка — класс в `{Module}/Exceptions`:

  ```php
  final class LessonLockedException extends ApiException
  {
      protected const string TRANSLATION_KEY = 'exceptions.course.lesson_locked';
      protected const int STATUS = 403;
  }
  ```

  Отвечает сама, в лог не пишется.
- **[тест]** Остальное оборачивается в тот же формат в `bootstrap/app.php`: валидация (422),
  вход (401), любой HTTP-код (404, 405, 429 с `Retry-After`…) — текстом из
  `exceptions.http.{код}`, сбой (500) — без подробностей (с `APP_DEBUG=true` — стандартный ответ
  Laravel с трассировкой). Тесты — `tests/Feature/ApiErrorResponseTest.php`.

## Документация API (OpenAPI)

`openapi.json` (OpenAPI 3.1) собирается из PHP-атрибутов `#[OA\…]` пакета zircote/swagger-php
(только dev, без doctrine/annotations): `make composer cmd=openapi`. Где что описано:
эндпоинт — `#[OA\Get]`/`#[OA\Post]` на `__invoke` контроллера рядом с маршрутом, тело запроса —
`#[OA\Schema]` на FormRequest, `data` ответа — `#[OA\Schema]` на Resource, обёртка ответа — на
`ApiResponder`, общее (вход, параметры, ошибки 401/404/422/429) — `app/Http/ApiSpecification.php`.
Схемы подключаются ссылкой-строкой `'#/components/schemas/Lesson'`: при нескольких схемах в
классе ссылка `LessonResource::class` берёт последнюю.

- **[тест]** `tests/Feature/OpenApiTest.php`: файл собран из текущего кода, описан каждый маршрут
  `api/*` и ничего лишнего, вход (`security`, 401), лимиты (429), параметры пути и поля формы —
  как у маршрута и в `rules()`.
- **[тест]** Каждый ответ feature-тестов к описанному эндпоинту сверяется со схемой
  (`Tests\TestCase::createTestResponse`, `tests/OpenApi`): код ответа описан, типы совпадают,
  лишних полей нет.
- Почему не генерация без атрибутов (dedoc/scramble): версия 0.x, общую обёртку и формат ошибок
  она выводит неточно — нужны свои расширения, а схемы с `mixed` (шаги урока) остаются
  размытыми. Атрибуты дают точную схему, а расхождение с кодом ловят тесты выше. Свой
  YAML без пакета — описание далеко от кода, генерировать нечем.

## Языки

Всё, что читает клиент, переводится: `lang/{ru,en}/` (`app.supported_locales`). Обращение — на «ты».

- `exceptions.php` — ошибки; `validation.php` — правила валидации (английские — из Laravel);
  с первыми эндпоинтами появятся `messages.php` (успешные ответы) и `fields.php` (названия
  полей, по формам: `fields.register.email`).
- `mail.php` — письма ученику (на языке аккаунта); `ru.json` — служебные строки шаблона писем
  Laravel («All rights reserved.» и т. п.), английские у Laravel свои.
- Ключи — через `App\Services\Translator::get()`. Язык ответа — из `Accept-Language`
  (middleware `SetLocale`, для всех запросов), иначе `ru`.
- **[тест]** `tests/Unit/LocaleFilesTest.php`: в каждом языке есть все ключи русского с теми же
  подстановками (`:attribute`, `:max`).

## Правила кода

- **[форматтер]** `declare(strict_types=1)` в каждом файле; все классы `final`, кроме
  абстрактных; нет неиспользуемых `use`. Стиль — PER-CS.
- **[PHPStan]** Level 10 + strict-rules. Переменные в camelCase; нет `dd()`, `dump()`,
  `var_dump()`; нет запросов к БД, вызовов репозиториев и контрактов в цикле (и в замыканиях
  `map`/`each`/`array_map`) — данные пачкой до цикла; нет `in_array` в цикле.
- Вне продакшена Eloquent строгий (`Model::shouldBeStrict`): ленивая загрузка связей (скрытый
  N+1) — ошибка.
- **[ревью]** Пароли — только через cast `hashed` / `Hash`, не логируются, не попадают в Resource.

## Безопасность учебной платформы

- **[PHPStan]** Сервер не выполняет код: нет `eval`, обратных кавычек, `exec`/`shell_exec`/
  `proc_open`…, `Process::run()`, `new Process()`. Код решения хранится как текст.
- **[ревью]** Результат проверки из браузера — заявление клиента, а не факт: прохождение
  засчитывается с ограничениями (одна задача — один раз, `throttle`).
- Размер запроса ограничен: валидация и `max_request_size` RoadRunner (2 МБ).
- Данных об учениках (среди них есть несовершеннолетние) — минимум: имя, почта, язык, прогресс.
- **[тест]** Наружу открыто только нужное: нет раздачи `storage/{path}` и маршрута
  `sanctum/csrf-cookie` (`tests/Feature/RoutesTest.php`). Вход — только Bearer-токен.

## Границы модулей

- **[PHPStan]** Из другого модуля можно брать только `Contracts/` и `Events/`: его модели,
  репозитории, Tasks и Actions — нельзя (`ModuleBoundaryRule`).
- **Свои таблицы.** Модуль один читает и пишет свои таблицы: к чужим нет JOIN, внешних ключей
  и Eloquent-связей; ссылка на чужое — колонка с ID (`lesson_id`) без внешнего ключа.
  Исключение — `user_id` с внешним ключом на `users`. **[ревью]**
- **Контракт** — интерфейс в `{Module}/Contracts/`, на входе и выходе DTO; данные — пачкой по
  списку ID. «Не нашлось» и «занято» — `null`, а не исключение: исключения модуля другой модуль
  не видит. Реализация — в `{Module}/Services/`, привязывается в провайдере модуля:

  ```php
  // app/Modules/Course/Contracts/LessonDirectory.php
  interface LessonDirectory
  {
      /** @param list<int> $ids  @return array<int, LessonSummaryDTO> */
      public function summaries(array $ids): array;
  }

  // CourseServiceProvider::register()
  $this->app->bind(LessonDirectory::class, LessonDirectoryService::class);
  ```

- **Полиморфные ссылки** (как `tokenable` у токенов Sanctum) — по псевдониму из morph map
  модуля-владельца (`user`), а не по имени класса: `Relation::enforceMorphMap` в его провайдере.

- **События между модулями** — `{Module}/Events/*Event` с ID и скалярами. Подписчик ставит задачу
  в очередь; повторная обработка не создаёт дублей (`Cache::add`, уникальные индексы).
- **Узел без состояния:** файлы — через `Storage::disk()`; кэш, блокировки и лимиты — в Redis;
  в памяти процесса между запросами ничего (RoadRunner держит процесс живым); задачи по
  расписанию — с `onOneServer()`.
- **Маршруты** модуля — под своим префиксом (`courses/…`, `progress/…`).
- **Счётчики** — в строке-владельце, `increment` / `decrement`.
- **Ломающие изменения API** — новым полем или эндпоинтом.

## Тексты на языке запроса

Тексты учебного контента — по языкам `{"ru": "…"}`; в ответ — одна строка через
`App\Services\Localized::pick()`: язык запроса, иначе русский.

## Настройки

В `config/` — только отличия от настроек Laravel по умолчанию (остальное фреймворк берёт из
`vendor/laravel/framework/config` и `.env`): языки, модель пользователя, CORS, диск без
раздачи файлов, Sanctum без cookie, папки контроллеров, доверенные обратные прокси
(`trustedproxy.php`, `TRUSTED_PROXIES`; production — `../deploy/README.md`).

## Зависимости

Порядок выбора: PHP и Laravel → уже подключённый пакет → свой небольшой код с тестами →
новый пакет (`../rules/dependencies.md`). CI: `composer audit --locked --no-dev --abandoned=fail`.

## Как добавить модуль

1. `app/Modules/Course/Providers/CourseServiceProvider.php` — `final class … extends
   ModuleServiceProvider`. Сразу решить, что модуль отдаёт другим (`Contracts/`), а что берёт.
2. Модель, миграция в `Database/Migrations`, фабрика (`#[UseFactory]` на модели).
3. Repository → Tasks → Action → Request/DTO → Controller с атрибутом маршрута → Resource.
4. Переводы в `lang/ru` и `lang/en`.
5. Атрибуты OpenAPI на Controller, Request и Resource, `make composer cmd=openapi`.
6. Feature-тест на каждый контроллер, `make check`.
