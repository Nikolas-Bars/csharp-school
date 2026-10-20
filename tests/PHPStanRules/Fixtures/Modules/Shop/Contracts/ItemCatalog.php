<?php

declare(strict_types=1);

namespace App\Modules\Shop\Contracts;

interface ItemCatalog
{
    public function priceOf(int $itemId): int;

    /**
     * @param list<int> $itemIds
     *
     * @return array<int, int>
     */
    public function pricesOf(array $itemIds): array;
}
