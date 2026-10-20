<?php

declare(strict_types=1);

namespace App\Modules\Arena\Actions;

use App\Modules\Arena\Models\Hero;
use App\Modules\Arena\Repositories\HeroRepository;
use App\Modules\Arena\Tasks\FindHeroTask;
use App\Modules\Shop\Contracts\ItemCatalog;
use App\Modules\Shop\Models\Item;
use Illuminate\Support\Facades\DB;

final readonly class StartBattleAction
{
    public function __construct(
        private FindHeroTask $findHero,
        private HeroRepository $heroes,
        private FinishBattleAction $finish,
        private ItemCatalog $catalog,
    ) {}

    /**
     * @param list<int> $heroIds
     */
    public function run(array $heroIds): void
    {
        DB::transaction(function () use ($heroIds): void {
            $this->findHero->run($heroIds[0] ?? 0);
            $this->heroes->find(1);
        });

        $this->finish->run();

        DB::select('select 1');
        Hero::where('hp', 0)->get();
        $hero = new Hero();
        $hero->save();

        foreach ($heroIds as $heroId) {
            $this->heroes->find($heroId);
            $this->catalog->priceOf($heroId);
        }

        array_map(fn(int $id): ?Hero => Hero::query()->find($id), $heroIds);

        $this->catalog->pricesOf($heroIds);
        Item::class;
    }
}
