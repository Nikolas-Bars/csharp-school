<?php

declare(strict_types=1);

namespace App\Modules\Course\Services;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Course\Tasks\ListLessonStepsTask;

final readonly class LessonDirectoryService implements LessonDirectory
{
    public function __construct(private ListLessonStepsTask $listLessons) {}

    public function lessonsOf(string $course): array
    {
        return $this->listLessons->run($course);
    }
}
