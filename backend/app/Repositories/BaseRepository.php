<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;

/**
 * Репозиторий — единственный слой, который ходит в БД (проверяет PHPStan):
 *
 *     final class LessonRepository extends BaseRepository  // @extends BaseRepository<Lesson>
 *
 * @template TModel of Model
 */
abstract class BaseRepository
{
    /**
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        return $this->model()::query();
    }

    /**
     * Курсор страницы из запроса (`next_cursor` прошлого ответа) — только если он в точности такой,
     * какой выдаёт Laravel для этой сортировки: значения ровно этих колонок и направление.
     * Подделанный или чужой — null, то есть первая страница: ошибка сервера на нём не случится.
     *
     * @param list<string> $columns колонки orderBy списка
     */
    protected function cursor(?string $encoded, array $columns): ?Cursor
    {
        if ($encoded === null || \strlen($encoded) > 1024 || preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            return null;
        }
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        $data = \is_string($decoded) ? json_decode($decoded, true) : null;
        if (!\is_array($data) || !\is_bool($data['_pointsToNextItems'] ?? null)) {
            return null;
        }
        $direction = $data['_pointsToNextItems'];
        unset($data['_pointsToNextItems']);

        $keys = array_keys($data);
        sort($keys);
        $expected = $columns;
        sort($expected);
        $scalar = array_filter($data, static fn(mixed $value): bool => \is_int($value) || \is_string($value));

        return $keys === $expected && \count($scalar) === \count($data) ? new Cursor($data, $direction) : null;
    }
}
