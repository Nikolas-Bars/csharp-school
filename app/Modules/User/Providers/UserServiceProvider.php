<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Modules\User\Contracts\UserAccounts;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserAccountsService;
use App\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

final class UserServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserAccounts::class, UserAccountsService::class);
    }

    public function boot(): void
    {
        parent::boot();

        // В чужих таблицах (токены Auth) пользователь записан как «user» + ID, а не именем класса:
        // другие модули не зависят от того, где лежит модель
        Relation::enforceMorphMap(['user' => User::class]);
    }
}
