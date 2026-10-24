<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tasks;

use Carbon\CarbonImmutable;

/**
 * Интервальное повторение: слово возвращается через растущие промежутки. Новое слово — в коробке 0
 * и ждёт повторения сразу. «Помню» — следующая коробка: через 3, 7, 14, 30 дней (в четвёртой слово
 * выучено и повторяется раз в 30 дней). «Не помню» — обратно в коробку 0, повторить завтра.
 * То же правило у гостя — frontend/src/utils/repetition.ts.
 */
final readonly class ScheduleReviewTask
{
    public const int LAST_BOX = 4;

    /** Через сколько дней повторить слово, попавшее в коробку */
    public const array DAYS = [1 => 3, 2 => 7, 3 => 14, 4 => 30];

    /** «Не помню» — завтра */
    public const int FORGOTTEN_DAYS = 1;

    /**
     * @return array{box: int, due_at: CarbonImmutable}
     */
    public function run(int $box, bool $remembered, CarbonImmutable $now): array
    {
        if (!$remembered) {
            return ['box' => 0, 'due_at' => $now->addDays(self::FORGOTTEN_DAYS)];
        }
        $next = min(max($box, 0) + 1, self::LAST_BOX);

        return ['box' => $next, 'due_at' => $now->addDays(self::DAYS[$next])];
    }
}
