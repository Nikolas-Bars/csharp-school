<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tasks;

use App\Modules\Vocabulary\DTO\LearnedWordDTO;
use App\Modules\Vocabulary\Models\LearnedWord;
use App\Modules\Vocabulary\Repositories\LearnedWordRepository;

final readonly class BuildVocabularyTask
{
    public function __construct(private LearnedWordRepository $learned) {}

    /**
     * Словарь ученика по курсу: только слова, которые есть в курсе сейчас.
     *
     * @param array<int, string> $words слова курса: id → slug
     *
     * @return list<LearnedWordDTO>
     */
    public function run(int $userId, array $words): array
    {
        if ($words === []) {
            return [];
        }

        return array_values($this->learned->forUser($userId, array_keys($words))->map(
            static fn(LearnedWord $word): LearnedWordDTO => new LearnedWordDTO($words[$word->word_id], $word->box, $word->due_at, $word->reviewed_at),
        )->all());
    }
}
