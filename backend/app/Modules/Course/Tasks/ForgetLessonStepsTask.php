<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use Illuminate\Support\Facades\Cache;

/** Курс заново импортирован — списки шагов уроков и слов в кэше устарели */
final readonly class ForgetLessonStepsTask
{
    public function run(string $course): void
    {
        Cache::forget(\sprintf(ListLessonStepsTask::CACHE_KEY, $course));
        Cache::forget(\sprintf(ListWordSlugsTask::CACHE_KEY, $course));
    }
}
