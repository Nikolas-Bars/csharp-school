<?php

declare(strict_types=1);

namespace App\Modules\Auth\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AuthServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Регистрация — не чаще 10 раз в минуту с одного адреса
        RateLimiter::for('auth', static fn(Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));

        // Вход — 5 попыток в минуту на пару «адрес + почта»: перебирать пароли долго,
        // а ученики за одним школьным адресом не мешают друг другу
        RateLimiter::for('login', static fn(Request $request): Limit => Limit::perMinute(5)->by(
            $request->ip() . '|' . $request->string('email')->trim()->lower()->toString(),
        ));
    }
}
