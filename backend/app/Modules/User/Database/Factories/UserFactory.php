<?php

declare(strict_types=1);

namespace App\Modules\User\Database\Factories;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    public const string PASSWORD = 'Password123';

    protected $model = User::class;

    /**
     * Хэш пароля считается один раз: bcrypt медленный нарочно, а фабрика в тестах
     * создаёт пользователей сотнями.
     */
    private static ?string $passwordHash = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'     => fake()->firstName(),
            'email'    => fake()->unique()->safeEmail(),
            'locale'   => 'ru',
            'password' => self::$passwordHash ??= Hash::make(self::PASSWORD),
        ];
    }
}
