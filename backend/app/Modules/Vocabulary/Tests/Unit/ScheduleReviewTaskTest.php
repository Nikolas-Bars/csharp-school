<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tests\Unit;

use App\Modules\Vocabulary\Tasks\ScheduleReviewTask;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScheduleReviewTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{int, bool, int, int}>
     */
    public static function reviews(): iterable
    {
        yield 'новое, помню — через 3 дня' => [0, true, 1, 3];
        yield 'помню — через 7' => [1, true, 2, 7];
        yield 'помню — через 14' => [2, true, 3, 14];
        yield 'помню — выучено, через 30' => [3, true, 4, 30];
        yield 'выученное остаётся выученным' => [4, true, 4, 30];
        yield 'не помню — завтра, с начала' => [3, false, 0, 1];
        yield 'новое, не помню — завтра' => [0, false, 0, 1];
    }

    #[DataProvider('reviews')]
    public function testNextReview(int $box, bool $remembered, int $nextBox, int $days): void
    {
        $now = CarbonImmutable::parse('2026-10-24 10:00:00');

        $next = (new ScheduleReviewTask())->run($box, $remembered, $now);

        self::assertSame($nextBox, $next['box']);
        self::assertSame('2026-10-24 10:00:00', $now->format('Y-m-d H:i:s'));
        self::assertTrue($now->addDays($days)->equalTo($next['due_at']));
    }
}
