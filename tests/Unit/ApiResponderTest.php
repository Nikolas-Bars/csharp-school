<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Tests\TestCase;

final class ApiResponderTest extends TestCase
{
    public function testOkAndCreatedWrapDataInCommonFormat(): void
    {
        self::assertSame(
            ['status' => 'success', 'message' => 'Готово.', 'data' => ['id' => 7], 'errors' => []],
            $this->decode(ApiResponder::ok(['id' => 7], 'Готово.')),
        );
        self::assertSame(201, ApiResponder::created(null, 'Создано.')->getStatusCode());
    }

    public function testErrorKeepsFieldErrorsAndHeaders(): void
    {
        $response = ApiResponder::error('Проверь поля.', 422, ['email' => ['Заполни поле.']], ['Retry-After' => '60']);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('60', $response->headers->get('Retry-After'));
        self::assertSame(
            ['status' => 'error', 'message' => 'Проверь поля.', 'data' => null, 'errors' => ['email' => ['Заполни поле.']]],
            $this->decode($response),
        );
    }

    public function testErrorsIsAlwaysAnObject(): void
    {
        // Пустой список ошибок — {} а не []: клиенту не нужно различать массив и объект
        self::assertStringContainsString('"errors":{}', (string) ApiResponder::error('Нет.', 404)->getContent());
    }

    public function testNoContentHasEmptyBody(): void
    {
        $response = ApiResponder::noContent();

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    public function testPageGivesCursorToNextPageAndNullOnLast(): void
    {
        $lessons = [['id' => 1], ['id' => 2], ['id' => 3]];

        $first = $this->decode(ApiResponder::page($this->paginator($lessons, 2), JsonResource::class, 'Уроки.'));
        $data = $first['data'];
        self::assertIsArray($data);
        self::assertSame([['id' => 1], ['id' => 2]], $data['items']);
        self::assertIsString($data['next_cursor']);
        self::assertSame(2, Cursor::fromEncoded($data['next_cursor'])?->parameter('id'));

        $last = $this->decode(ApiResponder::page($this->paginator($lessons, 5), JsonResource::class, 'Уроки.'));
        self::assertIsArray($last['data']);
        self::assertNull($last['data']['next_cursor']);
    }

    /**
     * @param list<array{id: int}> $items
     *
     * @return CursorPaginator<int, array{id: int}>
     */
    private function paginator(array $items, int $perPage): CursorPaginator
    {
        // Как отдаёт cursorPaginate(): на одну запись больше страницы — значит, дальше есть ещё
        return new CursorPaginator(\array_slice($items, 0, $perPage + 1), $perPage, null, ['parameters' => ['id']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(JsonResponse $response): array
    {
        /** @var array<string, mixed> */
        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
