<?php

declare(strict_types=1);

namespace App\Modules\Progress\Actions;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Progress\DTO\LessonProgressDTO;
use App\Modules\Progress\DTO\StepCompletionDTO;
use App\Modules\Progress\Tasks\BuildProgressTask;
use App\Modules\Progress\Tasks\SaveCompletedStepsTask;

/**
 * Засчитать пройденные шаги: один (задача только что решена) или пачкой (прогресс гостя после
 * входа). Результат проверки присылает браузер — это заявление, а не факт, поэтому шаг
 * засчитывается один раз, частота ограничена, а лишнее (нет такого урока) пропускается.
 */
final readonly class CompleteStepsAction
{
    public function __construct(
        private LessonDirectory $lessons,
        private SaveCompletedStepsTask $saveSteps,
        private BuildProgressTask $buildProgress,
    ) {}

    /**
     * @param list<StepCompletionDTO> $completions
     * @param bool                    $fromGuest   прогресс гостя после входа: сохранённый код не заменяется
     *
     * @return list<LessonProgressDTO> прогресс по курсу после сохранения
     */
    public function run(int $userId, string $course, array $completions, bool $fromGuest = false): array
    {
        $lessons = $this->lessons->lessonsOf($course);
        $this->saveSteps->run($userId, $lessons, $completions, $fromGuest);

        return $this->buildProgress->run($userId, $lessons);
    }
}
