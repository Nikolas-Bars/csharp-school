<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Resources;

use App\Modules\Progress\DTO\LessonProgressDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Прогресс урока: пройденные шаги и последний зачтённый код задач — чтобы продолжить на любом
 * устройстве. Фронт: LessonProgress в src/types/api.ts.
 *
 * @property LessonProgressDTO $resource
 */
#[OA\Schema(
    schema: 'LessonProgress',
    required: ['completed', 'solutions'],
    properties: [
        new OA\Property(property: 'completed', description: 'Пройденные шаги урока', type: 'array', items: new OA\Items(type: 'string'), example: ['hero-health', 'quiz']),
        new OA\Property(
            property: 'solutions',
            description: 'Id задачи → последний зачтённый код. Без решений — `{}`',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['hero-health' => "using System;\n\nint hp = 100;\nConsole.WriteLine(hp);"],
        ),
    ],
)]
final class LessonProgressResource extends JsonResource
{
    /**
     * @return array{completed: list<string>, solutions: object}
     */
    public function toArray(Request $request): array
    {
        return [
            'completed' => $this->resource->completed,
            // Объект даже без решений: {} а не []
            'solutions' => (object) $this->resource->solutions,
        ];
    }
}
