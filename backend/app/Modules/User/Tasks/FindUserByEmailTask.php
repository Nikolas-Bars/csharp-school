<?php

declare(strict_types=1);

namespace App\Modules\User\Tasks;

use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;

final readonly class FindUserByEmailTask
{
    public function __construct(private UserRepository $users) {}

    public function run(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }
}
