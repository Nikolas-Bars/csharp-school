<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tasks;

use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Progress\DTO\StepCompletionDTO;
use App\Modules\Progress\Repositories\CompletedStepRepository;

final readonly class SaveCompletedStepsTask
{
    public function __construct(private CompletedStepRepository $steps) {}

    /**
     * Сохраняет только шаги, которые есть в курсе: урок удалили или переименовали шаг —
     * запись из старого браузера молча пропускается. Код пройденного шага заменяет только
     * новый код: без кода шаг ничего не стирает, а прогресс гостя ($fromGuest) лишь дополняет
     * аккаунт — решение, уже сохранённое в аккаунте, свежее гостевого из старого браузера.
     *
     * @param list<LessonStepsDTO>      $lessons
     * @param list<StepCompletionDTO> $completions
     *
     * @return int сколько шагов записано
     */
    public function run(int $userId, array $lessons, array $completions, bool $fromGuest = false): int
    {
        $known = [];
        foreach ($lessons as $lesson) {
            $known[$lesson->path()] = ['id' => $lesson->id, 'steps' => array_flip($lesson->steps)];
        }

        $rows = [];
        foreach ($completions as $completion) {
            $lesson = $known["{$completion->module}/{$completion->lesson}"] ?? null;

            if ($lesson !== null && isset($lesson['steps'][$completion->step])) {
                // Ключ — урок и шаг: повтор в одном запросе не даёт двух строк
                $rows[$lesson['id'] . '|' . $completion->step] = [
                    'lesson_id' => $lesson['id'],
                    'step'      => $completion->step,
                    'code'      => $completion->step === LessonStepsDTO::QUIZ ? null : $completion->code,
                ];
            }
        }

        $rows = $this->withoutKeptCode($userId, $rows, $fromGuest);

        if ($rows !== []) {
            $this->steps->save($userId, $rows);
        }

        return \count($rows);
    }

    /**
     * Убирает шаги, которые уже пройдены и чей код менять не нужно.
     *
     * @param array<string, array{lesson_id: int, step: string, code: string|null}> $rows ключ — «урок|шаг»
     *
     * @return list<array{lesson_id: int, step: string, code: string|null}>
     */
    private function withoutKeptCode(int $userId, array $rows, bool $fromGuest): array
    {
        $saved = [];
        foreach ($this->steps->forUser($userId, array_values(array_unique(array_column($rows, 'lesson_id')))) as $step) {
            $saved["{$step->lesson_id}|{$step->step}"] = $step->code;
        }

        return array_values(array_filter(
            $rows,
            static fn(array $row, string $key): bool => !\array_key_exists($key, $saved)
                || ($row['code'] !== null && ($saved[$key] === null || !$fromGuest)),
            ARRAY_FILTER_USE_BOTH,
        ));
    }
}
