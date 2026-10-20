<?php

declare(strict_types=1);

namespace App\Modules\User\Database\Seeders;

use App\Modules\User\Database\Factories\UserFactory;
use Illuminate\Database\Seeder;

/**
 * Пользователи для локальной разработки, пароль у всех — UserFactory::PASSWORD.
 */
final class UserSeeder extends Seeder
{
    public function run(): void
    {
        UserFactory::new()->create(['nickname' => 'student', 'email' => 'student@example.com']);
        UserFactory::new()->count(3)->create();

        $this->command->info('Пользователи: student@example.com и ещё 3, пароль ' . UserFactory::PASSWORD);
    }
}
