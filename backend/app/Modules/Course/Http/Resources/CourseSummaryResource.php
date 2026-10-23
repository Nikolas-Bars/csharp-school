<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Курс на главной: название, описание, размер и первый урок. Фронт: CourseSummary в src/types/api.ts.
 *
 * @property Course $resource
 */
#[OA\Schema(
    schema: 'CourseSummary',
    description: 'Курс в списке: без модулей и уроков',
    required: ['slug', 'title', 'description', 'modules', 'lessons', 'start'],
    properties: [
        new OA\Property(property: 'slug', type: 'string', example: 'csharp'),
        new OA\Property(property: 'title', type: 'string', example: 'C# с нуля до первой игры'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'modules', description: 'Сколько модулей', type: 'integer', example: 5),
        new OA\Property(property: 'lessons', description: 'Сколько уроков вместе с контрольными', type: 'integer', example: 57),
        new OA\Property(
            property: 'start',
            description: 'Первый урок курса — с него начинают. В курсе всегда есть хотя бы один урок (course:import)',
            required: ['module', 'lesson'],
            properties: [
                new OA\Property(property: 'module', type: 'string', example: '01-first-programs'),
                new OA\Property(property: 'lesson', type: 'string', example: '01-hello'),
            ],
            type: ['object', 'null'],
        ),
    ],
)]
final class CourseSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $modules = $this->resource->modules;
        $first = $modules->first();
        $lesson = $first?->lessons->first();

        return [
            'slug'        => $this->resource->slug,
            'title'       => Localized::pick($this->resource->title),
            'description' => Localized::pick($this->resource->description ?? []),
            'modules'     => $modules->count(),
            'lessons'     => $modules->sum(static fn(CourseModule $module): int => $module->lessons->count()),
            'start'       => $first !== null && $lesson !== null ? ['module' => $first->slug, 'lesson' => $lesson->slug] : null,
        ];
    }
}
