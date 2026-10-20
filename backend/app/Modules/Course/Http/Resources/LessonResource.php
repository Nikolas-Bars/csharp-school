<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Lesson;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Урок на языке запроса: экраны теории (Markdown), задачи (условие, стартовый код, проверки
 * для runner, сообщения и подсказки), проверка знаний. Фронт: Lesson в src/types/api.ts.
 *
 * @property Lesson $resource
 */
final class LessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module = $this->resource->module;

        return [
            'slug'   => $this->resource->slug,
            'title'  => Localized::pick($this->resource->title),
            'module' => ['slug' => $module->slug, 'title' => Localized::pick($module->title)],
            'course' => ['slug' => $module->course->slug, 'title' => Localized::pick($module->course->title)],
            'steps'  => array_map(self::step(...), $this->resource->steps),
        ];
    }

    /**
     * @param array<string, mixed> $step
     *
     * @return array<string, mixed>
     */
    private static function step(array $step): array
    {
        return match ($step['type']) {
            'theory' => ['type' => 'theory', 'text' => self::text($step['text'])],
            'task'   => [
                'type'      => 'task',
                'id'        => $step['id'],
                'title'     => self::text($step['title']),
                'statement' => self::text($step['statement']),
                'starter'   => $step['starter'],
                'checks'    => $step['checks'],
                'messages'  => self::texts($step['messages']),
                'hints'     => self::texts($step['hints']),
            ],
            default => [
                'type'      => 'quiz',
                'questions' => array_map(static fn(mixed $question): array => [
                    'question'    => self::text(self::field($question, 'question')),
                    'options'     => self::texts(self::field($question, 'options')),
                    'answer'      => self::field($question, 'answer'),
                    'explanation' => self::text(self::field($question, 'explanation')),
                ], self::list($step['questions'])),
            ],
        };
    }

    private static function text(mixed $texts): string
    {
        return \is_array($texts) ? Localized::pick($texts) : '';
    }

    /**
     * @return list<string>
     */
    private static function texts(mixed $list): array
    {
        return array_map(self::text(...), self::list($list));
    }

    /**
     * @return list<mixed>
     */
    private static function list(mixed $value): array
    {
        return \is_array($value) ? array_values($value) : [];
    }

    private static function field(mixed $data, string $key): mixed
    {
        return \is_array($data) ? ($data[$key] ?? null) : null;
    }
}
