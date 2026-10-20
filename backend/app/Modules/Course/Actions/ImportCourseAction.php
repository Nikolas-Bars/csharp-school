<?php

declare(strict_types=1);

namespace App\Modules\Course\Actions;

use App\Modules\Course\DTO\CourseContentDTO;
use App\Modules\Course\Tasks\ForgetLessonStepsTask;
use App\Modules\Course\Tasks\ReadCourseContentTask;
use App\Modules\Course\Tasks\SaveCourseTask;
use Illuminate\Support\Facades\DB;

/** content/ → база. Сначала всё читается: ошибка в файле не оставит курс записанным наполовину */
final readonly class ImportCourseAction
{
    public function __construct(
        private ReadCourseContentTask $readContent,
        private SaveCourseTask $saveCourse,
        private ForgetLessonStepsTask $forgetLessonSteps,
    ) {}

    public function run(string $contentPath): CourseContentDTO
    {
        $content = $this->readContent->run($contentPath);

        DB::transaction(fn() => $this->saveCourse->run($content));
        $this->forgetLessonSteps->run($content->slug);

        return $content;
    }
}
