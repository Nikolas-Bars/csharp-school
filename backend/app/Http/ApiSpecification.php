<?php

declare(strict_types=1);

namespace App\Http;

use OpenApi\Attributes as OA;
use stdClass;

/**
 * Общие части спецификации OpenAPI (openapi.json): описание API, вход по токену, параметры и
 * ошибки из bootstrap/app.php. Обёртка ответа описана на ApiResponder, эндпоинты — атрибутами на
 * своих контроллерах, тела запросов — на FormRequest, данные ответов — на Resource.
 *
 * Атрибуты читает только генератор (zircote/swagger-php, dev-зависимость): `composer openapi`.
 * В production пакета нет, и класс атрибутов никто не загружает. Что спецификация совпадает
 * с кодом, проверяет tests/Feature/OpenApiTest.php, а ответы всех feature-тестов сверяются
 * со схемами в Tests\TestCase.
 */
#[OA\OpenApi(openapi: OA\OpenApi::VERSION_3_1_0)]
#[OA\Info(
    version: '1.0.0',
    description: "API обучающей платформы C# School.\n\n"
        . "Каждый ответ — JSON одного вида: `{ status, message, data, errors }`. `status` — `success` или `error`, "
        . "`message` — текст для человека на языке запроса (`Accept-Language`: `ru` или `en`, иначе `ru`), "
        . "`data` — данные (у ошибки `null`), `errors` — ошибки по полям формы (без ошибок — `{}`).\n\n"
        . 'Вход — по токену: `Authorization: Bearer <token>`, токен приходит в ответ на регистрацию и вход.',
    title: 'C# School API',
    license: new OA\License(name: 'MIT', identifier: 'MIT'),
)]
#[OA\Server(url: 'http://localhost:8290', description: 'Локально (Docker)')]
#[OA\SecurityScheme(
    securityScheme: 'bearer',
    type: 'http',
    description: 'Токен Laravel Sanctum из ответа `auth/register` или `auth/login`. Действует, пока ученик не выйдет на этом устройстве.',
    bearerFormat: '{id}|{секрет}',
    scheme: 'bearer',
)]
#[OA\Tag(name: 'Auth', description: 'Регистрация, вход, выход и текущий ученик')]
#[OA\Tag(name: 'Course', description: 'Курс: карта и уроки. Открыто всем, учиться можно без аккаунта')]
#[OA\Tag(name: 'Progress', description: 'Пройденные шаги и код решений. Только вошедшим')]
#[OA\Tag(name: 'Vocabulary', description: 'Мой словарь: выученные слова и интервальное повторение. Только вошедшим')]
#[OA\Parameter(
    parameter: 'AcceptLanguage',
    name: 'Accept-Language',
    description: 'Язык текстов ответа: `ru` или `en`. Другой или не указан — `ru`',
    in: 'header',
    required: false,
    schema: new OA\Schema(type: 'string', example: 'ru'),
)]
#[OA\Parameter(
    parameter: 'Cursor',
    name: 'cursor',
    description: 'Курсор страницы списка: `next_cursor` из прошлого ответа. Без него — первая страница',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string'),
)]
#[OA\Parameter(parameter: 'Course', name: 'course', description: 'Slug курса', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'csharp'))]
#[OA\Parameter(parameter: 'Module', name: 'module', description: 'Slug модуля курса', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01-first-programs'))]
#[OA\Parameter(parameter: 'Lesson', name: 'lesson', description: 'Slug урока', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '02-variables'))]
#[OA\Response(
    response: 'Unauthenticated',
    description: 'Не вошёл: нет токена, он неверный или отозван',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ApiError',
        example: ['status' => 'error', 'message' => 'Сначала войди в аккаунт.', 'data' => null, 'errors' => new stdClass()],
    ),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Нет такого адреса или записи (курса, урока). Текст — свой у каждой записи: «Такого курса нет.»',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ApiError',
        example: ['status' => 'error', 'message' => 'Такого адреса или записи нет.', 'data' => null, 'errors' => new stdClass()],
    ),
)]
#[OA\Response(
    response: 'ValidationFailed',
    description: 'Поля заполнены неправильно: сообщения по каждому полю — в `errors`',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ApiError',
        example: [
            'status'  => 'error',
            'message' => 'Проверь, правильно ли заполнены поля.',
            'data'    => null,
            'errors'  => ['email' => ['В поле «Почта» нужен адрес почты, например anya@example.com.']],
        ],
    ),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Слишком много запросов: повторить через `Retry-After` секунд',
    headers: [new OA\Header(header: 'Retry-After', description: 'Через сколько секунд можно повторить', schema: new OA\Schema(type: 'integer', example: 42))],
    content: new OA\JsonContent(
        ref: '#/components/schemas/ApiError',
        example: ['status' => 'error', 'message' => 'Слишком много попыток. Подожди минуту и попробуй снова.', 'data' => null, 'errors' => new stdClass()],
    ),
)]
final class ApiSpecification {}
