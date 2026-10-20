<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\Auth\Repositories\AccessTokenRepository;

final readonly class RevokeAccessTokenTask
{
    public function __construct(private AccessTokenRepository $tokens) {}

    public function run(int $tokenId, int $userId): void
    {
        $this->tokens->delete($tokenId, $userId);
    }
}
