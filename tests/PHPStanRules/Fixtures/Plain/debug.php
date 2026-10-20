<?php

declare(strict_types=1);

use Illuminate\Support\Collection;

/**
 * @param Collection<int, int> $scores
 */
function debugScores(Collection $scores): void
{
    dump($scores);
    var_dump($scores->all());
    printf('%d', $scores->count());
    // dd() завершает программу: код после него PHPStan не разбирает, поэтому он последний
    $scores->dd();
}
