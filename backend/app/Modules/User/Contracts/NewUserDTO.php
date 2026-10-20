<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts;

use SensitiveParameter;
use Spatie\LaravelData\Data;

/** Новый ученик: пароль открытым текстом, хэшируется при сохранении (cast hashed) */
final class NewUserDTO extends Data
{
    public function __construct(
        public string $nickname,
        public string $email,
        #[SensitiveParameter]
        public string $password,
        public string $locale,
    ) {}
}
