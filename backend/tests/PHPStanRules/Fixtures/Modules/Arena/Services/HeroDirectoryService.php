<?php

declare(strict_types=1);

namespace App\Modules\Arena\Services;

use App\Modules\Arena\Contracts\HeroDirectory;
use App\Modules\Arena\Tasks\FindHeroTask;

final readonly class HeroDirectoryService implements HeroDirectory
{
    public function __construct(private FindHeroTask $findHero) {}

    public function nameOf(int $heroId): string
    {
        return $this->findHero->run($heroId)?->getAttribute('name') ?? '';
    }
}
