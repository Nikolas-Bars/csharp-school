<?php

declare(strict_types=1);

namespace App\Modules\Arena\Repositories;

use App\Modules\Arena\Models\Hero;
use App\Repositories\BaseRepository;

/**
 * @extends BaseRepository<Hero>
 */
final class HeroRepository extends BaseRepository
{
    public function find(int $id): ?Hero
    {
        return $this->query()->where('id', $id)->first();
    }

    /**
     * @param list<int> $ids
     *
     * @return list<Hero>
     */
    public function findEach(array $ids): array
    {
        $heroes = [];

        foreach ($ids as $id) {
            $heroes[] = $this->query()->find($id);
        }

        return array_values(array_filter($heroes));
    }

    protected function model(): string
    {
        return Hero::class;
    }
}
