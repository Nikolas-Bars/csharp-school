<?php

declare(strict_types=1);

namespace App\Modules\Course\Repositories;

use App\Modules\Course\Models\Course;
use App\Repositories\BaseRepository;

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

    /**
     * @param array<string, string> $title
     */
    public function save(string $slug, array $title): Course
    {
        return $this->query()->updateOrCreate(['slug' => $slug], ['title' => $title]);
    }

    protected function model(): string
    {
        return Course::class;
    }
}
