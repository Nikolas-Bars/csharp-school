<?php

declare(strict_types=1);

namespace App\Modules\Progress\Actions;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Progress\DTO\LessonProgressDTO;
use App\Modules\Progress\Exceptions\LessonNotFoundException;
use App\Modules\Progress\Tasks\BuildProgressTask;

final readonly class ShowLessonProgressAction
{
    public function __construct(
        private LessonDirectory $lessons,
        private BuildProgressTask $buildProgress,
    ) {}

    public function run(int $userId, string $course, string $module, string $lesson): LessonProgressDTO
    {
        $found = array_values(array_filter(
            $this->lessons->lessonsOf($course),
            static fn(LessonStepsDTO $item): bool => $item->module === $module && $item->lesson === $lesson,
        ));

        return $found === [] ? throw new LessonNotFoundException() : $this->buildProgress->run($userId, $found)[0];
    }
}
