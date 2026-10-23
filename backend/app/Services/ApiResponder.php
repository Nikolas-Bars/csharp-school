<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Единственный формат ответа API: { "status": "success"|"error", "message", "data", "errors" }.
 * message — всегда переведённый текст (Translator::get), поэтому значения по умолчанию у него нет.
 * Схемы ApiSuccess и ApiError ниже — этот же формат в спецификации OpenAPI (openapi.json).
 */
#[OA\Schema(
    schema: 'ApiSuccess',
    description: 'Успешный ответ ApiResponder. Что лежит в `data` — у каждого эндпоинта своё',
    required: ['status', 'message', 'data', 'errors'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['success']),
        new OA\Property(property: 'message', description: 'Текст для человека на языке запроса', type: 'string'),
        new OA\Property(property: 'data', description: 'Данные ответа'),
        new OA\Property(property: 'errors', description: 'У успешного ответа всегда пусто', type: 'object', additionalProperties: false),
    ],
)]
#[OA\Schema(
    schema: 'ApiError',
    description: 'Ошибка: что случилось — в `message`, ошибки полей формы — в `errors`',
    required: ['status', 'message', 'data', 'errors'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['error']),
        new OA\Property(property: 'message', description: 'Текст для человека на языке запроса', type: 'string'),
        new OA\Property(property: 'data', type: 'null'),
        new OA\Property(
            property: 'errors',
            description: 'Поле формы → список сообщений; у ошибок не по полям — `{}`',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
)]
final class ApiResponder
{
    public static function ok(mixed $data, string $message): JsonResponse
    {
        return self::respond(Response::HTTP_OK, 'success', $message, $data);
    }

    public static function created(mixed $data, string $message): JsonResponse
    {
        return self::respond(Response::HTTP_CREATED, 'success', $message, $data);
    }

    /**
     * Страница списка: { items, next_cursor }. Следующая страница — запрос с ?cursor=next_cursor;
     * на последней странице next_cursor = null. Курсор вместо номера страницы: список не
     * съезжает, когда в него добавляют записи, и база не перебирает пропущенные строки.
     *
     * @template TValue
     *
     * @param CursorPaginator<array-key, TValue> $page
     * @param class-string<JsonResource>         $resource
     */
    public static function page(CursorPaginator $page, string $resource, string $message): JsonResponse
    {
        return self::ok([
            'items'       => $resource::collection($page->items()),
            'next_cursor' => $page->nextCursor()?->encode(),
        ], $message);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<array-key, mixed> $errors  ошибки по полям: поле => список сообщений
     * @param array<array-key, mixed> $headers например Retry-After при 429
     */
    public static function error(string $message, int $status, array $errors = [], array $headers = []): JsonResponse
    {
        return self::respond($status, 'error', $message, null, $errors, $headers);
    }

    /**
     * @param array<array-key, mixed> $errors
     * @param array<array-key, mixed> $headers
     */
    private static function respond(
        int $status,
        string $result,
        string $message,
        mixed $data,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse(
            ['status' => $result, 'message' => $message, 'data' => $data, 'errors' => (object) $errors],
            $status,
            $headers,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
