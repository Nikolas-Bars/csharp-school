<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Word;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Слово курса для словаря: карточка и урок, где оно появилось. Фронт: CourseWord в src/types/api.ts.
 *
 * @property Word $resource
 */
#[OA\Schema(
    schema: 'CourseWord',
    description: 'Карточка слова и урок, где оно появилось',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/WordCard'),
        new OA\Schema(
            required: ['module', 'lesson'],
            properties: [
                new OA\Property(property: 'module', required: ['slug', 'title'], properties: [
                    new OA\Property(property: 'slug', type: 'string', example: '02-present-continuous'),
                    new OA\Property(property: 'title', type: 'string', example: 'Present Continuous: сейчас и в эти дни'),
                ], type: 'object'),
                new OA\Property(property: 'lesson', required: ['slug', 'title'], properties: [
                    new OA\Property(property: 'slug', type: 'string', example: '01-right-now'),
                    new OA\Property(property: 'title', type: 'string', example: 'Present Continuous: что происходит прямо сейчас'),
                ], type: 'object'),
            ],
            type: 'object',
        ),
    ],
)]
final class WordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lesson = $this->resource->lesson;

        return [
            ...WordCardResource::card($this->resource->card),
            'module' => ['slug' => $lesson->module->slug, 'title' => Localized::pick($lesson->module->title)],
            'lesson' => ['slug' => $lesson->slug, 'title' => Localized::pick($lesson->title)],
        ];
    }
}
