<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTO\LoginDTO;
use App\Modules\Auth\DTO\SessionDTO;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Tasks\IssueAccessTokenTask;
use App\Modules\User\Contracts\UserAccounts;

final readonly class LoginAction
{
    public function __construct(
        private UserAccounts $accounts,
        private IssueAccessTokenTask $issueToken,
    ) {}

    public function run(LoginDTO $credentials): SessionDTO
    {
        $user = $this->accounts->findByCredentials($credentials->email, $credentials->password)
            ?? throw new InvalidCredentialsException();

        return new SessionDTO($user, $this->issueToken->run($user->id));
    }
}
