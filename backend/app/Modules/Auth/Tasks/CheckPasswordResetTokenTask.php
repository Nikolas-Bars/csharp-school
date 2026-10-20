<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\Auth\Repositories\PasswordResetTokenRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use SensitiveParameter;

final readonly class CheckPasswordResetTokenTask
{
    public function __construct(private PasswordResetTokenRepository $tokens) {}

    /** Ссылка для этой почты есть, не истекла (auth.passwords.users.expire, в минутах) и токен совпадает */
    public function run(string $email, #[SensitiveParameter] string $token): bool
    {
        $record = $this->tokens->findCreatedAfter(
            $email,
            Carbon::now()->subMinutes(Config::integer('auth.passwords.users.expire')),
        );

        return $record !== null && hash_equals($record->token, hash('sha256', $token));
    }
}
