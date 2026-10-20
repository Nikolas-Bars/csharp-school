<?php

declare(strict_types=1);

namespace App\Modules\Arena\Contracts;

interface HeroDirectory
{
    public function nameOf(int $heroId): string;
}
