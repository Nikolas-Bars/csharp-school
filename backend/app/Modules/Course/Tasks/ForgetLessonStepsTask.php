<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use Illuminate\Support\Facades\Cache;

/** Курс заново импортирован — список шагов в кэше устарел */
final readonly class ForgetLessonStepsTask
{
    public function run(string $course): void
    {
        Cache::forget(\sprintf(ListLessonStepsTask::CACHE_KEY, $course));
    }
}
