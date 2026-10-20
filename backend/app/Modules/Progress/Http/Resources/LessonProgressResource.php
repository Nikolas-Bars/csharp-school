<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Resources;

use App\Modules\Progress\DTO\LessonProgressDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Прогресс урока: пройденные шаги и последний зачтённый код задач — чтобы продолжить на любом
 * устройстве. Фронт: LessonProgress в src/types/api.ts.
 *
 * @property LessonProgressDTO $resource
 */
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
