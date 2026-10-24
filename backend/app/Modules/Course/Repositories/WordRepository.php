<?php

declare(strict_types=1);

namespace App\Modules\Course\Repositories;

use App\Modules\Course\Models\Word;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<Word>
 */
final class WordRepository extends BaseRepository
{
    /**
     * Карточки курса ровно такие, как в rows: вставка-обновление по slug (id не меняются — на них
     * ссылается словарь учеников), лишние удаляются.
     *
     * @param list<array{lesson_id: int, slug: string, position: int, card: array<array-key, mixed>}> $rows
     */
    public function replace(int $courseId, array $rows): void
    {
        $now = now();

        if ($rows !== []) {
            $this->query()->upsert(
                array_map(static fn(array $row): array => [
                    'course_id'  => $courseId,
                    'lesson_id'  => $row['lesson_id'],
                    'slug'       => $row['slug'],
                    'position'   => $row['position'],
                    'card'       => json_encode($row['card'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rows),
                ['course_id', 'slug'],
                ['lesson_id', 'position', 'card', 'updated_at'],
            );
        }

        $this->query()
            ->where('course_id', $courseId)
            ->whereNotIn('slug', array_column($rows, 'slug'))
            ->delete();
    }

    /**
     * Карточки курса по порядку, с уроком и модулем — для словаря.
     *
     * @return CursorPaginator<int, Word>
     */
    public function page(int $courseId, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->query()
            ->with('lesson:id,course_module_id,slug,title', 'lesson.module:id,slug,title')
            ->where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $this->cursor($cursor, ['position', 'id']));
    }

    /**
     * Все слова курса.
     *
     * @return array<int, string> id → slug
     */
    public function slugsOf(string $course): array
    {
        /** @var array<int, string> */
        return $this->query()
            ->whereHas('course', static fn(Builder $query) => $query->where('slug', $course))
            ->pluck('slug', 'id')
            ->all();
    }

    protected function model(): string
    {
        return Word::class;
    }
}
