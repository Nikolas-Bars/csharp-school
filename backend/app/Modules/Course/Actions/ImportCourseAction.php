<?php

declare(strict_types=1);

namespace App\Modules\Course\Actions;

use App\Modules\Course\DTO\CourseContentDTO;
use App\Modules\Course\Repositories\CourseRepository;
use App\Modules\Course\Tasks\ForgetLessonStepsTask;
use App\Modules\Course\Tasks\ReadCourseContentTask;
use App\Modules\Course\Tasks\SaveCourseTask;
use Illuminate\Support\Facades\DB;

/**
 * content/ → база: все курсы из courses.json. Сначала всё читается, потом пишется одной
 * транзакцией: ошибка в любом файле не оставит ни один курс записанным наполовину.
 * Курс, которого больше нет в courses.json, уходит с главной, но не удаляется: на его уроки
 * ссылается прогресс учеников.
 */
final readonly class ImportCourseAction
{
    public function __construct(
        private ReadCourseContentTask $readContent,
        private SaveCourseTask $saveCourse,
        private ForgetLessonStepsTask $forgetLessonSteps,
        private CourseRepository $courseRepository,
    ) {}

    /**
     * @return non-empty-list<CourseContentDTO>
     */
    public function run(string $contentPath): array
    {
        $courses = $this->readContent->run($contentPath);

        DB::transaction(function () use ($courses): void {
            foreach ($courses as $course) {
                $this->saveCourse->run($course);
            }
            $this->courseRepository->unlistExcept(array_map(static fn(CourseContentDTO $course): string => $course->slug, $courses));
        });
        foreach ($courses as $course) {
            $this->forgetLessonSteps->run($course->slug);
        }

        return $courses;
    }
}
