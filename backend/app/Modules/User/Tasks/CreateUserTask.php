<?php

declare(strict_types=1);

namespace App\Modules\User\Tasks;

use App\Modules\User\Contracts\NewUserDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;

final readonly class CreateUserTask
{
    public function __construct(private UserRepository $users) {}

    /**
     * @return User|null null — почта уже занята
     */
    public function run(NewUserDTO $user): ?User
    {
        return $this->users->create([
            'nickname' => $user->nickname,
            'email'    => $user->email,
            'password' => $user->password,
            'locale'   => $user->locale,
        ]);
    }
}
