<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tasks;

use App\Modules\Vocabulary\DTO\NewWordDTO;
use App\Modules\Vocabulary\Repositories\LearnedWordRepository;
use Carbon\CarbonImmutable;

final readonly class AddWordsTask
{
    public function __construct(private LearnedWordRepository $learned) {}

    /**
     * Добавляет в словарь слова курса; неизвестные (карточку убрали из курса) пропускаются, уже
     * выученные не меняются. Новое слово ждёт повторения сразу. Словарь гостя ($fromGuest) переносит
     * своё расписание, но только для слов, которых в аккаунте ещё нет: аккаунт свежее старого браузера.
     * Срок гостя позже, чем даёт его коробка (часы браузера убежали вперёд), обрезается до неё.
     *
     * @param array<int, string> $words слова курса: id → slug
     * @param list<NewWordDTO>   $added
     */
    public function run(int $userId, array $words, array $added, bool $fromGuest, CarbonImmutable $now): void
    {
        $ids = array_flip($words);
        $rows = [];

        foreach ($added as $word) {
            $id = $ids[$word->word] ?? null;
            if ($id === null) {
                continue;
            }
            if (!$fromGuest || $word->box === null || $word->dueAt === null) {
                $rows[$id] = ['word_id' => $id, 'box' => 0, 'due_at' => $now];

                continue;
            }
            $latest = $now->addDays(ScheduleReviewTask::DAYS[$word->box] ?? ScheduleReviewTask::FORGOTTEN_DAYS);
            $rows[$id] = ['word_id' => $id, 'box' => $word->box, 'due_at' => min($word->dueAt->toImmutable(), $latest)];
        }

        if ($rows !== []) {
            $this->learned->addMissing($userId, array_values($rows));
        }
    }
}
