<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\Auth\Repositories\PasswordResetTokenRepository;

final readonly class DeletePasswordResetTokenTask
{
    public function __construct(private PasswordResetTokenRepository $tokens) {}

    /**
     * @return bool false — ссылку уже использовали
     */
    public function run(string $email): bool
    {
        return $this->tokens->delete($email);
    }
}
