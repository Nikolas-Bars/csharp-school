<?php

declare(strict_types=1);

namespace App\Modules\User\Tasks;

use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;

final readonly class FindUserTask
{
    public function __construct(private UserRepository $users) {}

    public function run(int $userId): ?User
    {
        return $this->users->findById($userId);
    }
}
