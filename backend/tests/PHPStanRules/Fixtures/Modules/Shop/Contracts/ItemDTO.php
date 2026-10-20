<?php

declare(strict_types=1);

namespace App\Modules\Shop\Contracts;

use Spatie\LaravelData\Data;

final class ItemDTO extends Data
{
    public function __construct(public string $name) {}

    public function label(): string
    {
        return mb_strtoupper($this->name);
    }
}
