<?php

declare(strict_types=1);

namespace App\Modules\Arena\Http\Controllers;

use App\Modules\Arena\Actions\StartBattleAction;
use App\Modules\Arena\Repositories\HeroRepository;
use App\Modules\Arena\Tasks\FindHeroTask;

final readonly class ShowHeroController
{
    public function __construct(
        private StartBattleAction $start,
        private FindHeroTask $findHero,
        private HeroRepository $heroes,
    ) {}

    public function __invoke(): void
    {
        $this->start->run([1]);
        $this->findHero->run(1);
        $this->heroes->find(1);
    }

    public function helper(): void {}
}
