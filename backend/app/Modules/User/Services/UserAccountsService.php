<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Contracts\NewUserDTO;
use App\Modules\User\Contracts\UserAccounts;
use App\Modules\User\Contracts\UserDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Tasks\ChangePasswordTask;
use App\Modules\User\Tasks\CreateUserTask;
use App\Modules\User\Tasks\FindUserByEmailTask;
use App\Modules\User\Tasks\FindUserTask;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

final readonly class UserAccountsService implements UserAccounts
{
    public function __construct(
        private CreateUserTask $createUser,
        private FindUserByEmailTask $findUserByEmail,
        private FindUserTask $findUser,
        private ChangePasswordTask $changePassword,
    ) {}

    public function register(NewUserDTO $user): ?UserDTO
    {
        // Сначала проверка, затем уникальный индекс — на случай двух запросов одновременно
        if ($this->findUserByEmail->run($user->email) !== null) {
            return null;
        }

        $created = $this->createUser->run($user);

        return $created === null ? null : self::toDTO($created);
    }

    public function findByCredentials(string $email, #[SensitiveParameter] string $password): ?UserDTO
    {
        $user = $this->findUserByEmail->run($email);

        return $user !== null && Hash::check($password, $user->password) ? self::toDTO($user) : null;
    }

    public function find(int $userId): ?UserDTO
    {
        $user = $this->findUser->run($userId);

        return $user === null ? null : self::toDTO($user);
    }

    public function findByEmail(string $email): ?UserDTO
    {
        $user = $this->findUserByEmail->run($email);

        return $user === null ? null : self::toDTO($user);
    }

    public function changePassword(int $userId, #[SensitiveParameter] string $password): bool
    {
        return $this->changePassword->run($userId, $password);
    }

    private static function toDTO(User $user): UserDTO
    {
        return new UserDTO($user->id, $user->nickname, $user->email, $user->locale);
    }
}
