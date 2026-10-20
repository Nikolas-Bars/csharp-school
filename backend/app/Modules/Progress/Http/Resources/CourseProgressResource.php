<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Resources;

use App\Modules\Progress\DTO\LessonProgressDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Прогресс по курсу для карты: какие шаги каждого урока пройдены. Без кода решений.
 * Фронт: CourseProgress в src/types/api.ts.
 *
 * @property list<LessonProgressDTO> $resource
 */
#[OA\Schema(
    schema: 'CourseProgress',
    description: 'Прогресс по курсу для карты, без кода решений',
    required: ['lessons'],
    properties: [
        new OA\Property(property: 'lessons', type: 'array', items: new OA\Items(
            required: ['module', 'lesson', 'steps', 'completed', 'done'],
            properties: [
                new OA\Property(property: 'module', type: 'string', example: '01-first-programs'),
                new OA\Property(property: 'lesson', type: 'string', example: '02-variables'),
                new OA\Property(property: 'steps', description: 'Все засчитываемые шаги урока по порядку', type: 'array', items: new OA\Items(type: 'string'), example: ['hero-health', 'take-damage', 'quiz']),
                new OA\Property(property: 'completed', description: 'Пройденные из них', type: 'array', items: new OA\Items(type: 'string'), example: ['hero-health']),
                new OA\Property(property: 'done', description: 'Урок пройден: пройдены все шаги', type: 'boolean', example: false),
            ],
            type: 'object',
        )),
    ],
)]
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
