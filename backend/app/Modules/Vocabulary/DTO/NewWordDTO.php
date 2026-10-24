<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\DTO;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/** Слово, которое ученик выучил: из урока — только slug, из словаря гостя — ещё и его расписание */
final class NewWordDTO extends Data
{
    public function __construct(
        public string $word,
        public ?int $box = null,
        public ?Carbon $dueAt = null,
    ) {}
}
