<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use App\Modules\User\Contracts\UserDTO;
use SensitiveParameter;
use Spatie\LaravelData\Data;

/** Вход состоялся: кто вошёл и токен для Authorization: Bearer */
final class SessionDTO extends Data
{
    public function __construct(
        public UserDTO $user,
        #[SensitiveParameter]
        public string $token,
    ) {}
}
