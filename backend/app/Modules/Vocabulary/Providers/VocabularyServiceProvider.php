<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

final class VocabularyServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Запись: слова урока и итоги повторения уходят пачкой, 30 раз в минуту хватит любому ученику
        RateLimiter::for('vocabulary', static fn(Request $request): Limit => Limit::perMinute(30)->by('user|' . Auth::id()));
        // Чтение: словарь открывается часто, но не сотни раз в минуту
        RateLimiter::for('vocabulary-read', static fn(Request $request): Limit => Limit::perMinute(120)->by('user|' . Auth::id()));
    }
}
