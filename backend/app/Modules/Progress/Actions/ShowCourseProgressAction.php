<?php

declare(strict_types=1);

namespace App\Modules\Progress\Actions;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Progress\DTO\LessonProgressDTO;
use App\Modules\Progress\Tasks\BuildProgressTask;

final readonly class ShowCourseProgressAction
{
    public function __construct(
        private LessonDirectory $lessons,
        private BuildProgressTask $buildProgress,
    ) {}

    /**
     * @return list<LessonProgressDTO>
     */
    public function run(int $userId, string $course): array
    {
        return $this->buildProgress->run($userId, $this->lessons->lessonsOf($course));
    }
}
