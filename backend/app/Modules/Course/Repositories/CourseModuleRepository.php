<?php

declare(strict_types=1);

namespace App\Modules\Course\Repositories;

use App\Modules\Course\Models\CourseModule;
use App\Repositories\BaseRepository;

/**
 * @extends BaseRepository<CourseModule>
 */
final class CourseModuleRepository extends BaseRepository
{
    /**
     * Модули курса ровно такие, как в rows: одна вставка-обновление пачкой, лишние удаляются
     * (вместе со своими уроками — внешний ключ cascade).
     *
     * @param list<array{slug: string, position: int, title: array<string, string>, description: array<string, string>}> $rows
     *
     * @return array<string, int> slug → id
     */
    public function replace(int $courseId, array $rows): array
    {
        $now = now();

        $this->query()->upsert(
            array_map(static fn(array $row): array => [
                'course_id' => $courseId,
                'slug'      => $row['slug'],
                'position'  => $row['position'],
                // upsert идёт мимо cast-ов модели: JSON — вручную
                'title'       => json_encode($row['title'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'description' => json_encode($row['description'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $rows),
            ['course_id', 'slug'],
            ['position', 'title', 'description', 'updated_at'],
        );

        $slugs = array_column($rows, 'slug');
        $this->query()->where('course_id', $courseId)->whereNotIn('slug', $slugs)->delete();

        /** @var array<string, int> */
        return $this->query()->where('course_id', $courseId)->pluck('id', 'slug')->all();
    }

    protected function model(): string
    {
        return CourseModule::class;
    }
}
