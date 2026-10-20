<?php

declare(strict_types=1);

namespace App\Modules\User\Tasks;

use App\Modules\User\Repositories\UserRepository;
use SensitiveParameter;

final readonly class ChangePasswordTask
{
    public function __construct(private UserRepository $users) {}

    /**
     * @return bool false — такого ученика нет
     */
    public function run(int $userId, #[SensitiveParameter] string $password): bool
    {
        return $this->users->updatePassword($userId, $password);
    }
}
