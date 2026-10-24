<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Repositories;

use App\Modules\Vocabulary\Models\LearnedWord;
use App\Repositories\BaseRepository;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<LearnedWord>
 */
final class LearnedWordRepository extends BaseRepository
{
    /**
     * @param list<int> $wordIds
     *
     * @return Collection<int, LearnedWord>
     */
    public function forUser(int $userId, array $wordIds): Collection
    {
        return $this->query()->where('user_id', $userId)->whereIn('word_id', $wordIds)->orderBy('id')->get();
    }

    /**
     * Пачкой: новые слова записываются, уже выученные не меняются.
     *
     * @param list<array{word_id: int, box: int, due_at: DateTimeInterface}> $words
     */
    public function addMissing(int $userId, array $words): void
    {
        $now = now();

        $this->query()->insertOrIgnore(array_map(static fn(array $word): array => [
            'user_id'    => $userId,
            'word_id'    => $word['word_id'],
            'box'        => $word['box'],
            'due_at'     => $word['due_at'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $words));
    }

    /**
     * Пачкой: новое расписание повторённых слов.
     *
     * @param list<array{word_id: int, box: int, due_at: DateTimeInterface}> $words
     */
    public function reschedule(int $userId, array $words): void
    {
        $now = now();

        $this->query()->upsert(
            array_map(static fn(array $word): array => [
                'user_id'     => $userId,
                'word_id'     => $word['word_id'],
                'box'         => $word['box'],
                'due_at'      => $word['due_at'],
                'reviewed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $words),
            ['user_id', 'word_id'],
            ['box', 'due_at', 'reviewed_at', 'updated_at'],
        );
    }

    protected function model(): string
    {
        return LearnedWord::class;
    }
}
