<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Modules\Course\Models\Lesson;
use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Урок на языке запроса: экраны теории (Markdown), задачи C# (условие, стартовый код, проверки
 * для runner, сообщения и подсказки), практика английского, проверка знаний (вопросы C# или упражнения
 * английского). Фронт: Lesson в src/types/api.ts.
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
            new OA\Schema(ref: '#/components/schemas/WordsStep'),
            new OA\Schema(ref: '#/components/schemas/PracticeStep'),
            new OA\Schema(ref: '#/components/schemas/QuizStep'),
            new OA\Schema(ref: '#/components/schemas/ExerciseQuizStep'),
        ])),
    ],
)]
#[OA\Schema(
    schema: 'WordsStep',
    description: 'Новые слова урока английского: карточки по одной. В прогресс не засчитывается, слова уходят в словарь ученика',
    required: ['type', 'words'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['words']),
        new OA\Property(property: 'words', type: 'array', items: new OA\Items(ref: '#/components/schemas/WordCard')),
    ],
)]
#[OA\Schema(
    schema: 'PracticeStep',
    description: 'Практика английского: упражнения по одному, проверка — в браузере',
    required: ['type', 'id', 'exercises'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['practice']),
        new OA\Property(property: 'id', description: 'Id практики — шаг для прогресса', type: 'string', example: 'form'),
        new OA\Property(property: 'exercises', type: 'array', items: new OA\Items(ref: '#/components/schemas/Exercise')),
    ],
)]
#[OA\Schema(
    schema: 'ExerciseQuizStep',
    description: 'Проверка знаний в конце урока английского — те же упражнения, что в практике',
    required: ['type', 'exercises'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['quiz']),
        new OA\Property(property: 'exercises', type: 'array', items: new OA\Items(ref: '#/components/schemas/Exercise')),
    ],
)]
#[OA\Schema(
    schema: 'Exercise',
    description: 'Упражнение английского. Ответ проверяет браузер (frontend/src/utils/answers.ts): регистр, пробелы, '
        . 'точка в конце и краткие формы (don\'t = do not) не важны, если не `exact`',
    oneOf: [
        new OA\Schema(
            title: 'choice',
            description: 'Выбрать вариант',
            required: ['kind', 'prompt', 'explanation', 'options', 'answer'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['choice']),
                new OA\Property(property: 'prompt', description: 'Задание', type: 'string', example: 'Как сказать «Я сейчас читаю»?'),
                new OA\Property(property: 'explanation', description: 'Почему верный ответ верный', type: 'string'),
                new OA\Property(property: 'options', type: 'array', items: new OA\Items(
                    required: ['text', 'why'],
                    properties: [
                        new OA\Property(property: 'text', type: 'string', example: 'I am reading now.'),
                        new OA\Property(property: 'why', description: 'Почему этот неверный вариант не подходит', type: ['string', 'null']),
                    ],
                    type: 'object',
                )),
                new OA\Property(property: 'answer', description: 'Номер верного варианта, с 0', type: 'integer', example: 1),
            ],
            type: 'object',
        ),
        new OA\Schema(
            title: 'written',
            description: 'Ответ словами: gap — вставить в пропуск ___, order — собрать фразу из плиток, translate — перевести, '
                . 'fix — найти слово с ошибкой (номер `wrong` среди слов `text`) и написать, как правильно',
            required: ['kind', 'prompt', 'explanation', 'accept', 'mistakes', 'hint', 'exact'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['gap', 'order', 'translate', 'fix']),
                new OA\Property(property: 'prompt', type: 'string'),
                new OA\Property(property: 'explanation', type: 'string'),
                new OA\Property(property: 'text', description: 'gap, fix — английская фраза; translate — фраза на языке ученика', type: 'string', example: 'She ___ (cook) dinner at the moment.'),
                new OA\Property(property: 'tiles', description: 'order — плитки по порядку показа, бывают лишние', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'wrong', description: 'fix — номер слова с ошибкой, с 0', type: 'integer'),
                new OA\Property(property: 'accept', description: 'Правильные ответы; {a|b} — любой из вариантов', type: 'array', items: new OA\Items(type: 'string'), example: ['is cooking']),
                new OA\Property(property: 'mistakes', description: 'Частые ошибки и объяснение каждой', type: 'array', items: new OA\Items(
                    required: ['answer', 'why'],
                    properties: [
                        new OA\Property(property: 'answer', type: 'string', example: 'cooks'),
                        new OA\Property(property: 'why', type: 'string'),
                    ],
                    type: 'object',
                )),
                new OA\Property(property: 'hint', description: 'Для неверного ответа не из mistakes — без готового ответа', type: 'string'),
                new OA\Property(property: 'exact', description: 'Краткие и полные формы различаются (задание на краткую форму)', type: 'boolean'),
            ],
            type: 'object',
        ),
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
                new OA\Property(
                    property: 'set',
                    description: 'Запустить с другими начальными значениями: имя переменной основного кода → значение (массив — для int[] и List<int>)',
                    type: ['object', 'null'],
                    example: ['height' => 5, 'scores' => [5, 9, 1]],
                    additionalProperties: new OA\AdditionalProperties(oneOf: [
                        new OA\Schema(type: 'number'),
                        new OA\Schema(type: 'string'),
                        new OA\Schema(type: 'boolean'),
                        new OA\Schema(type: 'array', items: new OA\Items(oneOf: [
                            new OA\Schema(type: 'number'),
                            new OA\Schema(type: 'string'),
                            new OA\Schema(type: 'boolean'),
                        ])),
                    ]),
                ),
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
                new OA\Property(property: 'construct', type: 'string', enum: ['if', 'else', 'for', 'foreach', 'while', 'doWhile', 'switch', 'method', 'class', 'break', 'continue', 'constructor', 'property', 'privateSet', 'this', 'enum', 'inheritance', 'virtual', 'override', 'protected', 'baseConstructor']),
                new OA\Property(property: 'min', type: 'integer', default: 1, minimum: 1),
            ],
            type: 'object',
        ),
        new OA\Schema(
            title: 'call',
            description: 'В коде вызывается метод (если задан `caller` — из тела этого метода)',
            required: ['kind', 'method'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['call']),
                new OA\Property(property: 'method', description: 'Полное имя', type: 'string', example: 'System.Console.WriteLine'),
                new OA\Property(property: 'caller', description: 'Вызов засчитывается только в теле этого метода (полное имя), конструктора (`Hero.Hero`) или свойства (`Hero.Hp`)', type: ['string', 'null'], example: 'Program.Report'),
            ],
            type: 'object',
        ),
        new OA\Schema(
            title: 'access',
            description: 'Значение поля или свойства читается в коде не меньше `min` раз (запись не считается)',
            required: ['kind', 'member'],
            properties: [
                new OA\Property(property: 'kind', type: 'string', enum: ['access']),
                new OA\Property(property: 'member', description: 'Класс и поле, свойство или значение перечисления (`State.Attack`)', type: 'string', example: 'Hero.hp'),
                new OA\Property(property: 'min', type: 'integer', default: 1, minimum: 1),
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
            'words'    => ['type' => 'words', 'words' => array_map(WordCardResource::card(...), self::list($step['words']))],
            'practice' => [
                'type'      => 'practice',
                'id'        => $step['id'],
                'exercises' => array_map(self::exercise(...), self::list($step['exercises'])),
            ],
            default => \array_key_exists('exercises', $step)
                ? ['type' => 'quiz', 'exercises' => array_map(self::exercise(...), self::list($step['exercises']))]
                : [
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

    /**
     * Упражнение английского на языке запроса: тексты для ученика переведены, английские фразы
     * (варианты, плитки, правильные ответы) — как есть. Формат — content/README.md.
     *
     * @return array<string, mixed>
     */
    private static function exercise(mixed $exercise): array
    {
        $kind = self::field($exercise, 'kind');
        $common = ['kind' => $kind, 'prompt' => self::text(self::field($exercise, 'prompt')), 'explanation' => self::text(self::field($exercise, 'explanation'))];

        if ($kind === 'choice') {
            return [...$common, 'options' => array_map(static fn(mixed $option): array => [
                'text' => self::field($option, 'text'),
                'why'  => \is_array(self::field($option, 'why')) ? self::text(self::field($option, 'why')) : null,
            ], self::list(self::field($exercise, 'options'))), 'answer' => self::field($exercise, 'answer')];
        }

        return [
            ...$common,
            ...match ($kind) {
                'gap'       => ['text' => self::field($exercise, 'text')],
                'order'     => ['tiles' => self::list(self::field($exercise, 'tiles'))],
                'translate' => ['text' => self::text(self::field($exercise, 'text'))],
                default     => ['text' => self::field($exercise, 'text'), 'wrong' => self::field($exercise, 'wrong')],
            },
            'accept'   => self::list(self::field($exercise, 'accept')),
            'mistakes' => array_map(static fn(mixed $mistake): array => [
                'answer' => self::field($mistake, 'answer'),
                'why'    => self::text(self::field($mistake, 'why')),
            ], self::list(self::field($exercise, 'mistakes'))),
            'hint'  => self::text(self::field($exercise, 'hint')),
            'exact' => self::field($exercise, 'forms') === 'exact',
        ];
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
