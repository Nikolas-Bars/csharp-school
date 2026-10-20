<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use SensitiveParameter;
use Spatie\LaravelData\Data;

final class RegisterDTO extends Data
{
    public function __construct(
        public string $nickname,
        public string $email,
        #[SensitiveParameter]
        public string $password,
    ) {}
}
