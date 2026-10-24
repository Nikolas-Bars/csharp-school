<?php

declare(strict_types=1);

namespace App\Modules\Course\Repositories;

use App\Modules\Course\Models\Course;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * @extends BaseRepository<Course>
 */
final class CourseRepository extends BaseRepository
{
    /** Курс со всеми модулями и уроками — для карты курса */
    public function findWithStructure(string $slug): ?Course
    {
        return $this->query()->with('modules.lessons')->where('slug', $slug)->first();
    }

    public function findBySlug(string $slug): ?Course
    {
        return $this->query()->where('slug', $slug)->first();
    }

    /**
     * Курсы для главной по порядку — только те, что есть в courses.json (position > 0). Модули и
     * уроки — только slug и порядок (счётчики и первый урок), без шагов: шаги урока — самая тяжёлая колонка.
     *
     * @return CursorPaginator<int, Course>
     */
    public function page(int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->query()
            ->with(['modules:id,course_id,slug,position', 'modules.lessons:id,course_module_id,slug,position'])
            ->where('position', '>', 0)
            ->orderBy('position')
            ->orderBy('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $this->cursor($cursor, ['position', 'id']));
    }

    /**
     * Курсы не из этого списка уходят с главной (position = 0). Не удаляются: на их уроки
     * ссылается прогресс учеников, и по прямой ссылке они открываются как раньше.
     *
     * @param list<string> $slugs
     */
    public function unlistExcept(array $slugs): void
    {
        $this->query()->whereNotIn('slug', $slugs)->update(['position' => 0]);
    }

    /**
     * @param array<string, string> $title
     * @param array<string, string> $description
     */
    public function save(string $slug, array $title, array $description, int $position): Course
    {
        return $this->query()->updateOrCreate(['slug' => $slug], ['title' => $title, 'description' => $description, 'position' => $position]);
    }

    protected function model(): string
    {
        return Course::class;
    }
}
