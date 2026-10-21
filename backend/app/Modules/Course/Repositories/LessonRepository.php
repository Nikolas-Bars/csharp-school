<?php

declare(strict_types=1);

namespace App\Modules\Course\Repositories;

use App\Modules\Course\Enums\LessonKind;
use App\Modules\Course\Models\Lesson;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<Lesson>
 */
final class LessonRepository extends BaseRepository
{
    public function findByPath(string $course, string $module, string $lesson): ?Lesson
    {
        return $this->query()
            ->with('module.course')
            ->where('slug', $lesson)
            ->whereHas('module', static fn(Builder $query) => $query
                ->where('slug', $module)
                ->whereHas('course', static fn(Builder $query) => $query->where('slug', $course)))
            ->first();
    }

    /**
     * Уроки модулей ровно такие, как в rows: вставка-обновление пачкой, лишние удаляются.
     *
     * @param list<int>                                                                                                        $moduleIds все модули курса
     * @param list<array{course_module_id: int, slug: string, position: int, kind: LessonKind, title: array<string, string>, steps: list<mixed>}> $rows
     */
    public function replace(array $moduleIds, array $rows): void
    {
        $now = now();

        $this->query()->upsert(
            array_map(static fn(array $row): array => [
                'course_module_id' => $row['course_module_id'],
                'slug'             => $row['slug'],
                'position'         => $row['position'],
                'kind'             => $row['kind']->value,
                'title'            => json_encode($row['title'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'steps'            => json_encode($row['steps'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at'       => $now,
                'updated_at'       => $now,
            ], $rows),
            ['course_module_id', 'slug'],
            ['position', 'kind', 'title', 'steps', 'updated_at'],
        );

        // Ключ урока — «модуль|slug»: одинаковые slug в разных модулях — разные уроки
        $keep = array_flip(array_map(static fn(array $row): string => $row['course_module_id'] . '|' . $row['slug'], $rows));
        $stale = $this->query()
            ->whereIn('course_module_id', $moduleIds)
            ->get(['id', 'course_module_id', 'slug'])
            ->reject(static fn(Lesson $lesson): bool => isset($keep["{$lesson->course_module_id}|{$lesson->slug}"]))
            ->modelKeys();

        $this->query()->whereKey($stale)->delete();
    }

    protected function model(): string
    {
        return Lesson::class;
    }
}
