<?php

declare(strict_types=1);

namespace App\Modules\Progress\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

final class ProgressServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Шаги засчитывает браузер: 30 раз в минуту хватит любому ученику, а накрутить — долго
        RateLimiter::for('progress', static fn(Request $request): Limit => Limit::perMinute(30)->by('user|' . Auth::id()));
        // Чтение: карта и уроки открываются часто, но не сотни раз в минуту
        RateLimiter::for('progress-read', static fn(Request $request): Limit => Limit::perMinute(120)->by('user|' . Auth::id()));
    }
}
