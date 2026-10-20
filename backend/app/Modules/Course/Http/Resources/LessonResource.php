<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Lesson;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Урок на языке запроса: экраны теории (Markdown), задачи (условие, стартовый код, проверки
 * для runner, сообщения и подсказки), проверка знаний. Фронт: Lesson в src/types/api.ts.
 * Проверки задач (Check) — формат runner: runner/src/Runner.Core/Protocol/Checks.cs.
 *
 * @property Lesson $resource
 */
#[OA\Schema(
    schema: 'Lesson',
    description: 'Урок: тексты уже на языке запроса',
    required: ['slug', 'title', 'module', 'course', 'steps'],
    properties: [
        new OA\Property(property: 'slug', type: 'string', example: '02-variables'),
        new OA\Property(property: 'title', type: 'string', example: 'Переменные'),
        new OA\Property(property: 'module', required: ['slug', 'title'], properties: [
            new OA\Property(property: 'slug', type: 'string', example: '01-first-programs'),
            new OA\Property(property: 'title', type: 'string', example: 'Первые программы'),
        ], type: 'object'),
        new OA\Property(property: 'course', required: ['slug', 'title'], properties: [
            new OA\Property(property: 'slug', type: 'string', example: 'csharp'),
            new OA\Property(property: 'title', type: 'string', example: 'C# с нуля до первой игры'),
        ], type: 'object'),
        new OA\Property(property: 'steps', description: 'Шаги урока по порядку', type: 'array', items: new OA\Items(oneOf: [
            new OA\Schema(ref: '#/components/schemas/TheoryStep'),
            new OA\Schema(ref: '#/components/schemas/TaskStep'),
            new OA\Schema(ref: '#/components/schemas/QuizStep'),
        ])),
    ],
)]
#[OA\Schema(
    schema: 'TheoryStep',
    description: 'Экран теории — одна идея',
    required: ['type', 'text'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['theory']),
        new OA\Property(property: 'text', description: 'Markdown', type: 'string', example: "# Переменные\n\nПеременная — коробка с именем…"),
    ],
)]
#[OA\Schema(
    schema: 'TaskStep',
    description: 'Задача с кодом. Проверки выполняет runner в браузере ученика, сервер код не запускает',
    required: ['type', 'id', 'title', 'statement', 'starter', 'checks', 'messages', 'hints'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['task']),
        new OA\Property(property: 'id', description: 'Id задачи — шаг для прогресса', type: 'string', example: 'hero-health'),
        new OA\Property(property: 'title', type: 'string', example: 'Здоровье героя'),
        new OA\Property(property: 'statement', description: 'Условие: Markdown', type: 'string'),
        new OA\Property(property: 'starter', description: 'Стартовый код в редакторе', type: 'string', example: "using System;\n"),
        new OA\Property(property: 'checks', type: 'array', items: new OA\Items(ref: '#/components/schemas/Check')),
        new OA\Property(property: 'messages', description: 'Что не так и куда смотреть — по одному на проверку, в том же порядке', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'hints', description: 'Ровно три: намёк → подробнее → почти решение', type: 'array', items: new OA\Items(type: 'string')),
    ],
)]
#[OA\Schema(
    schema: 'QuizStep',
    description: 'Проверка знаний в конце урока',
    required: ['type', 'questions'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['quiz']),
        new OA\Property(property: 'questions', type: 'array', items: new OA\Items(
            required: ['question', 'options', 'answer', 'explanation'],
            properties: [
                new OA\Property(property: 'question', type: 'string', example: 'Какой тип у числа 5?'),
                new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string'), example: ['int', 'string']),
                new OA\Property(property: 'answer', description: 'Номер верного варианта в options, с 0', type: 'integer', example: 0),
                new OA\Property(property: 'explanation', description: 'Почему так — показывается после ответа', type: 'string'),
            ],
            type: 'object',
        )),
    ],
)]
#[OA\Schema(
    schema: 'Check',
    description: 'Проверка решения для runner. Текст при провале — в `messages` задачи',
    oneOf: [
        new OA\Schema(
            title: 'output',
            description: 'Программа с таким вводом печатает такой текст',
            required: ['kind', 'expected'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['output']),
                new OA\Property(property: 'expected', type: 'string', example: 'Привет'),
                new OA\Property(property: 'stdin', description: 'Что ввести с клавиатуры', type: ['string', 'null']),
                new OA\Property(property: 'match', description: 'exact — вывод целиком, contains — текст где-то в выводе', type: 'string', default: 'exact', enum: ['exact', 'contains']),
            ],
            type: 'object',
        ),
        new OA\Schema(
            title: 'variable',
            description: 'В коде объявлена переменная с таким именем (регистр важен) и, если задан, такого типа',
            required: ['kind', 'name'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['variable']),
                new OA\Property(property: 'name', type: 'string', example: 'hp'),
                new OA\Property(property: 'type', type: ['string', 'null'], example: 'int'),
            ],
            type: 'object',
        ),
        new OA\Schema(
            title: 'construct',
            description: 'Конструкция встречается в коде не меньше `min` раз',
            required: ['kind', 'construct'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['construct']),
                new OA\Property(property: 'construct', type: 'string', enum: ['if', 'else', 'for', 'foreach', 'while', 'doWhile', 'switch', 'method', 'class']),
                new OA\Property(property: 'min', type: 'integer', default: 1, minimum: 1),
            ],
            type: 'object',
        ),
        new OA\Schema(
            title: 'call',
            description: 'В коде вызывается метод',
            required: ['kind', 'method'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['call']),
                new OA\Property(property: 'method', description: 'Полное имя', type: 'string', example: 'System.Console.WriteLine'),
            ],
            type: 'object',
        ),
    ],
)]
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
