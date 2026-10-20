<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Карта курса: модули и уроки без содержимого. Фронт: Course в src/types/api.ts.
 *
 * @property Course $resource
 */
#[OA\Schema(
    schema: 'Course',
    description: 'Карта курса: модули и уроки без содержимого',
    required: ['slug', 'title', 'modules'],
    properties: [
        new OA\Property(property: 'slug', type: 'string', example: 'csharp'),
        new OA\Property(property: 'title', type: 'string', example: 'C# с нуля до первой игры'),
        new OA\Property(property: 'modules', type: 'array', items: new OA\Items(
            required: ['slug', 'title', 'description', 'lessons'],
            properties: [
                new OA\Property(property: 'slug', type: 'string', example: '01-first-programs'),
                new OA\Property(property: 'title', type: 'string', example: 'Первые программы'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'lessons', type: 'array', items: new OA\Items(
                    required: ['slug', 'title', 'tasks', 'steps'],
                    properties: [
                        new OA\Property(property: 'slug', type: 'string', example: '02-variables'),
                        new OA\Property(property: 'title', type: 'string', example: 'Переменные'),
                        new OA\Property(property: 'tasks', description: 'Сколько в уроке задач с кодом', type: 'integer', example: 2),
                        new OA\Property(
                            property: 'steps',
                            description: 'Засчитываемые шаги: id задач и `quiz`. Урок пройден, когда пройдены все — так и гость видит, пройден ли урок',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['hero-health', 'take-damage', 'quiz'],
                        ),
                    ],
                    type: 'object',
                )),
            ],
            type: 'object',
        )),
    ],
)]
final class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug'    => $this->resource->slug,
            'title'   => Localized::pick($this->resource->title),
            'modules' => $this->resource->modules->map(static fn(CourseModule $module): array => [
                'slug'        => $module->slug,
                'title'       => Localized::pick($module->title),
                'description' => Localized::pick($module->description),
                'lessons'     => $module->lessons->map(static fn(Lesson $lesson): array => [
                    'slug'  => $lesson->slug,
                    'title' => Localized::pick($lesson->title),
                    'tasks' => \count(array_filter($lesson->steps, static fn(array $step): bool => $step['type'] === 'task')),
                    // Засчитываемые шаги (id задач и quiz) — чтобы и гость видел, пройден ли урок
                    'steps' => $lesson->countedSteps(),
                ])->values(),
            ])->values(),
        ];
    }
}
