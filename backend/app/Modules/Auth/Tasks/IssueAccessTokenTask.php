<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\Auth\Repositories\AccessTokenRepository;

final readonly class IssueAccessTokenTask
{
    /** Имя токена — откуда вошли; пока вход только с сайта */
    private const string NAME = 'web';

    public function __construct(private AccessTokenRepository $tokens) {}

    public function run(int $userId): string
    {
        return $this->tokens->create($userId, self::NAME);
    }
}
