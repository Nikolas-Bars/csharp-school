<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Карта курса: модули и уроки без содержимого. Фронт: Course в src/types/api.ts.
 *
 * @property Course $resource
 */
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
