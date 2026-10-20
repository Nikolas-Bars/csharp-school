<?php

declare(strict_types=1);

namespace App\Modules\Arena\Tasks;

use App\Modules\Arena\Actions\FinishBattleAction;
use App\Modules\Arena\Models\Hero;
use App\Modules\Arena\Repositories\HeroRepository;

final readonly class FindHeroTask
{
    public function __construct(
        private HeroRepository $heroes,
        private FinishBattleAction $finish,
    ) {}

    public function run(int $id): ?Hero
    {
        $this->finish->run();

        return $this->heroes->find($id);
    }
}
