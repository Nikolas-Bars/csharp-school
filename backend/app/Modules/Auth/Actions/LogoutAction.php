<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Tasks\RevokeAccessTokenTask;

/** Выход на этом устройстве: удаляется только текущий токен, на других устройствах вход остаётся */
final readonly class LogoutAction
{
    public function __construct(private RevokeAccessTokenTask $revokeToken) {}

    public function run(int $tokenId, int $userId): void
    {
        $this->revokeToken->run($tokenId, $userId);
    }
}
