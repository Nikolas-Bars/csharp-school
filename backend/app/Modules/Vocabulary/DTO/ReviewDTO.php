<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\DTO;

use Spatie\LaravelData\Data;

/** Итог повторения одного слова: «Помню» или «Не помню» */
final class ReviewDTO extends Data
{
    public function __construct(
        public string $word,
        public bool $remembered,
    ) {}
}
