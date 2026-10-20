<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\User\Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Пароль тестовых пользователей лежит в открытом репозитории: на проде это был бы открытый вход
        if (App::isProduction()) {
            $this->command->warn('Продакшн: тестовые пользователи не создаются.');

            return;
        }

        $this->call(UserSeeder::class);
    }
}
