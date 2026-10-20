<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\User\Contracts\UserAccounts;
use App\Modules\User\Contracts\UserDTO;
use Illuminate\Auth\AuthenticationException;

final readonly class CurrentUserAction
{
    public function __construct(private UserAccounts $accounts) {}

    public function run(int $userId): UserDTO
    {
        // Токен проверен, но пользователя уже нет (удалён) — для клиента это тот же «не вошёл»
        return $this->accounts->find($userId) ?? throw new AuthenticationException();
    }
}
