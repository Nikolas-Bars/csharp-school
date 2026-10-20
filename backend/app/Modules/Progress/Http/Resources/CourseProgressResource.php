<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Resources;

use App\Modules\Progress\DTO\LessonProgressDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Прогресс по курсу для карты: какие шаги каждого урока пройдены. Без кода решений.
 * Фронт: CourseProgress в src/types/api.ts.
 *
 * @property list<LessonProgressDTO> $resource
 */
final class CourseProgressResource extends JsonResource
{
    /**
     * @return array{lessons: list<array{module: string, lesson: string, steps: list<string>, completed: list<string>, done: bool}>}
     */
    public function toArray(Request $request): array
    {
        return [
            'lessons' => array_map(static fn(LessonProgressDTO $lesson): array => [
                'module'    => $lesson->module,
                'lesson'    => $lesson->lesson,
                'steps'     => $lesson->steps,
                'completed' => $lesson->completed,
                'done'      => $lesson->done(),
            ], $this->resource),
        ];
    }
}
