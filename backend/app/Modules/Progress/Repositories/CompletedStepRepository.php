<?php

declare(strict_types=1);

namespace App\Modules\Progress\Repositories;

use App\Modules\Progress\Models\CompletedStep;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<CompletedStep>
 */
final class CompletedStepRepository extends BaseRepository
{
    /**
     * @param list<int> $lessonIds
     *
     * @return Collection<int, CompletedStep>
     */
    public function forUser(int $userId, array $lessonIds): Collection
    {
        return $this->query()->where('user_id', $userId)->whereIn('lesson_id', $lessonIds)->get();
    }

    /**
     * Пачкой: новые шаги записываются, уже пройденные — только получают новый код
     * (время первого прохождения не меняется).
     *
     * @param list<array{lesson_id: int, step: string, code: string|null}> $steps
     */
    public function save(int $userId, array $steps): void
    {
        $now = now();

        $this->query()->upsert(
            array_map(static fn(array $step): array => [
                'user_id'      => $userId,
                'lesson_id'    => $step['lesson_id'],
                'step'         => $step['step'],
                'code'         => $step['code'],
                'completed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ], $steps),
            ['user_id', 'lesson_id', 'step'],
            ['code', 'updated_at'],
        );
    }

    protected function model(): string
    {
        return CompletedStep::class;
    }
}
