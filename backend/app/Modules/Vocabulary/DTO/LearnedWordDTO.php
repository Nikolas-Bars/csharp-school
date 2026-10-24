<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\DTO;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/** Слово словаря ученика: slug карточки и расписание повторения */
final class LearnedWordDTO extends Data
{
    public function __construct(
        public string $word,
        public int $box,
        public Carbon $dueAt,
        public ?Carbon $reviewedAt,
    ) {}
}
