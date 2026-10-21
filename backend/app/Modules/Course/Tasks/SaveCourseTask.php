<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use App\Modules\Course\DTO\CourseContentDTO;
use App\Modules\Course\Repositories\CourseModuleRepository;
use App\Modules\Course\Repositories\CourseRepository;
use App\Modules\Course\Repositories\LessonRepository;

/**
 * Записывает курс в базу так, чтобы она совпала с content/: тем же slug — те же id (на них
 * будет ссылаться прогресс), удалённое из контента удаляется. Запросов — по одному на таблицу.
 */
final readonly class SaveCourseTask
{
    public function __construct(
        private CourseRepository $courses,
        private CourseModuleRepository $modules,
        private LessonRepository $lessons,
    ) {}

    public function run(CourseContentDTO $content): void
    {
        $course = $this->courses->save($content->slug, $content->title);

        $moduleIds = $this->modules->replace($course->id, array_map(static fn($module, int $position): array => [
            'slug'        => $module->slug,
            'position'    => $position + 1,
            'title'       => $module->title,
            'description' => $module->description,
        ], $content->modules, array_keys($content->modules)));

        $lessons = [];
        foreach ($content->modules as $module) {
            foreach ($module->lessons as $position => $lesson) {
                $lessons[] = [
                    'course_module_id' => $moduleIds[$module->slug],
                    'slug'             => $lesson->slug,
                    'position'         => $position + 1,
                    'kind'             => $lesson->kind,
                    'title'            => $lesson->title,
                    'steps'            => $lesson->steps,
                ];
            }
        }

        $this->lessons->replace(array_values($moduleIds), $lessons);
    }
}
