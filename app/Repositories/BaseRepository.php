<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
}
