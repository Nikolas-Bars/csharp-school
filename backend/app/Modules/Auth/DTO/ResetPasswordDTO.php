<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use SensitiveParameter;
use Spatie\LaravelData\Data;

/** Новый пароль: почта и токен из ссылки в письме */
final class ResetPasswordDTO extends Data
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $token,
        #[SensitiveParameter]
        public string $password,
    ) {}
}
