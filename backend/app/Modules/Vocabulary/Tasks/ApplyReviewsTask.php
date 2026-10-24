<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tasks;

use App\Modules\Vocabulary\DTO\ReviewDTO;
use App\Modules\Vocabulary\Repositories\LearnedWordRepository;
use Carbon\CarbonImmutable;

final readonly class ApplyReviewsTask
{
    /**
     * Повторить можно слово, срок которого наступил «сегодня» по часам ученика: сервер не знает его
     * часовой пояс, поэтому запас — сутки
     */
    public const int DUE_SLACK_HOURS = 24;

    /**
     * Только что повторённое слово второй раз подряд не засчитывается: «Не помню» на телефоне и «Помню» в
     * старой вкладке ноутбука — первое и последнее слово за ответом, а не сдвиг на 3 дня
     */
    public const int REPEAT_GUARD_MINUTES = 5;

    public function __construct(
        private LearnedWordRepository $learned,
        private ScheduleReviewTask $schedule,
    ) {}

    /**
     * Новое расписание повторённых слов. Слово не из словаря или ещё не пора повторять — пропускается:
     * так повтор того же слова или старый запрос не сдвигают его вперёд.
     *
     * @param array<int, string> $words   слова курса: id → slug
     * @param list<ReviewDTO>    $reviews
     */
    public function run(int $userId, array $words, array $reviews, CarbonImmutable $now): void
    {
        $ids = array_flip($words);
        $byWord = [];
        foreach ($reviews as $review) {
            $id = $ids[$review->word] ?? null;
            // Одно слово в пачке — один раз: засчитывается первый ответ
            if ($id !== null && !isset($byWord[$id])) {
                $byWord[$id] = $review->remembered;
            }
        }
        if ($byWord === []) {
            return;
        }

        $deadline = $now->addHours(self::DUE_SLACK_HOURS);
        $justReviewed = $now->subMinutes(self::REPEAT_GUARD_MINUTES);
        $rows = [];
        foreach ($this->learned->forUser($userId, array_keys($byWord)) as $learned) {
            if ($learned->due_at->greaterThan($deadline) || $learned->reviewed_at?->greaterThan($justReviewed) === true) {
                continue;
            }
            $next = $this->schedule->run($learned->box, $byWord[$learned->word_id], $now);
            $rows[] = ['word_id' => $learned->word_id, 'box' => $next['box'], 'due_at' => $next['due_at']];
        }

        if ($rows !== []) {
            $this->learned->reschedule($userId, $rows);
        }
    }
}
