<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tasks;

use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Progress\DTO\LessonProgressDTO;
use App\Modules\Progress\Models\CompletedStep;
use App\Modules\Progress\Repositories\CompletedStepRepository;

/** Прогресс ученика по урокам: один запрос на все уроки сразу */
final readonly class BuildProgressTask
{
    public function __construct(private CompletedStepRepository $steps) {}

    /**
     * @param list<LessonStepsDTO> $lessons
     *
     * @return list<LessonProgressDTO>
     */
    public function run(int $userId, array $lessons): array
    {
        $byLesson = $this->steps
            ->forUser($userId, array_map(static fn(LessonStepsDTO $lesson): int => $lesson->id, $lessons))
            ->groupBy('lesson_id');

        return array_map(static function (LessonStepsDTO $lesson) use ($byLesson): LessonProgressDTO {
            $completed = $byLesson->get($lesson->id, collect());
            /** @var list<string> $names */
            $names = $completed->pluck('step')->all();
            $done = array_flip($names);
            $solutions = [];

            foreach ($completed as $step) {
                /** @var CompletedStep $step */
                if ($step->code !== null) {
                    $solutions[$step->step] = $step->code;
                }
            }

            return new LessonProgressDTO(
                $lesson->module,
                $lesson->lesson,
                $lesson->steps,
                // В порядке урока, только существующие шаги
                array_values(array_filter($lesson->steps, static fn(string $step): bool => isset($done[$step]))),
                $solutions,
            );
        }, $lessons);
    }
}
