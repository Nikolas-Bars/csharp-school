<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts;

use Spatie\LaravelData\Data;

/** Ученик для других модулей: без пароля */
final class UserDTO extends Data
{
    public function __construct(
        public int $id,
        public string $nickname,
        public string $email,
        public string $locale,
    ) {}
}
