<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Requests;

use App\Modules\Progress\DTO\StepCompletionDTO;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CompleteStepsRequest',
    required: ['steps'],
    properties: [
        new OA\Property(property: 'steps', type: 'array', items: new OA\Items(
            required: ['module', 'lesson', 'step'],
            properties: [
                new OA\Property(property: 'module', type: 'string', pattern: self::SLUG, example: '01-first-programs'),
                new OA\Property(property: 'lesson', type: 'string', pattern: self::SLUG, example: '02-variables'),
                new OA\Property(property: 'step', description: 'Id задачи или `quiz`', type: 'string', pattern: self::SLUG, example: 'hero-health'),
                new OA\Property(
                    property: 'code',
                    description: 'Код, который прошёл проверки (для задачи). Нет или `null` — сохранённый код не меняется',
                    type: ['string', 'null'],
                    maxLength: self::MAX_CODE,
                    example: "using System;\n\nint hp = 100;\nConsole.WriteLine(hp);",
                ),
            ],
            type: 'object',
            additionalProperties: false,
        ), maxItems: self::MAX_STEPS, minItems: 1),
        new OA\Property(property: 'guest', description: 'Прогресс гостя после входа: дополняет аккаунт, сохранённый код не заменяет', type: 'boolean', default: false),
    ],
)]
final class CompleteStepsRequest extends FormRequest
{
    /** Прогресс гостя за весь курс — сотни шагов, не больше */
    private const int MAX_STEPS = 500;

    /** Код решения — как текст; больше 20 000 символов задача курса не требует */
    private const int MAX_CODE = 20_000;

    /** Slug модуля, урока и id шага */
    private const string SLUG = '^[a-z0-9][a-z0-9-]{0,63}$';

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $slug = 'regex:/' . self::SLUG . '/';

        return [
            'steps'          => ['required', 'array', 'min:1', 'max:' . self::MAX_STEPS],
            'steps.*'        => ['required', 'array:module,lesson,step,code'],
            'steps.*.module' => ['required', 'string', $slug],
            'steps.*.lesson' => ['required', 'string', $slug],
            'steps.*.step'   => ['required', 'string', $slug],
            'steps.*.code'   => ['nullable', 'string', 'max:' . self::MAX_CODE],
            // Прогресс гостя после входа: дополняет аккаунт, сохранённый код не заменяет
            'guest' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['steps' => Translator::get('fields.progress.steps'), 'steps.*.code' => Translator::get('fields.progress.code')];
    }

    public function fromGuest(): bool
    {
        return $this->boolean('guest');
    }

    /**
     * @return list<StepCompletionDTO>
     */
    public function toDTOs(): array
    {
        /** @var list<array{module: string, lesson: string, step: string, code?: string|null}> $steps */
        $steps = $this->validated('steps');

        return array_map(
            static fn(array $step): StepCompletionDTO => new StepCompletionDTO($step['module'], $step['lesson'], $step['step'], $step['code'] ?? null),
            $steps,
        );
    }
}
