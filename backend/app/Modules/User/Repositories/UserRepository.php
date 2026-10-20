<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Database\UniqueConstraintViolationException;

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

    protected function model(): string
    {
        return User::class;
    }
}
