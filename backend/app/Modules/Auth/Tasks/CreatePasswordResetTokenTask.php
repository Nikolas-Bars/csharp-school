<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\Auth\Repositories\PasswordResetTokenRepository;
use Illuminate\Support\Str;

final readonly class CreatePasswordResetTokenTask
{
    public function __construct(private PasswordResetTokenRepository $tokens) {}

    /**
     * Случайный токен из 64 символов: подобрать его нельзя, поэтому в базе хватает SHA-256, как у
     * токенов входа. bcrypt тут не нужен — он только замедлил бы ответ.
     *
     * @return string токен для ссылки в письме
     */
    public function run(string $email): string
    {
        $token = Str::random(64);

        $this->tokens->replace($email, hash('sha256', $token));

        return $token;
    }
}
