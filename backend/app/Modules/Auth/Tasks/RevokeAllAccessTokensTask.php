<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\Auth\Repositories\AccessTokenRepository;

final readonly class RevokeAllAccessTokensTask
{
    public function __construct(private AccessTokenRepository $tokens) {}

    public function run(int $userId): void
    {
        $this->tokens->deleteAllOf($userId);
    }
}
