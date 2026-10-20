<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use SensitiveParameter;

/**
 * @extends BaseRepository<User>
 */
final class UserRepository extends BaseRepository
{
    public function findById(int $id): ?User
    {
        return $this->query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    /**
     * @param array{nickname: string, email: string, password: string, locale: string} $attributes
     *
     * @return User|null null — почта уже занята (уникальный индекс: два запроса одновременно)
     */
    public function create(array $attributes): ?User
    {
        try {
            return $this->query()->create($attributes);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    /**
     * Пароль присваивается модели, а не пишется запросом UPDATE: так его хэширует cast hashed.
     *
     * @return bool false — такого ученика нет
     */
    public function updatePassword(int $id, #[SensitiveParameter] string $password): bool
    {
        $user = $this->findById($id);

        if ($user === null) {
            return false;
        }

        $user->password = $password;
        $user->save();

        return true;
    }

    protected function model(): string
    {
        return User::class;
    }
}
