<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Requests;

use App\Modules\Progress\DTO\StepCompletionDTO;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;

final class CompleteStepsRequest extends FormRequest
{
    /** Прогресс гостя за весь курс — сотни шагов, не больше */
    private const int MAX_STEPS = 500;

    /** Код решения — как текст; больше 20 000 символов задача курса не требует */
    private const int MAX_CODE = 20_000;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $slug = 'regex:/^[a-z0-9][a-z0-9-]{0,63}$/';

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
