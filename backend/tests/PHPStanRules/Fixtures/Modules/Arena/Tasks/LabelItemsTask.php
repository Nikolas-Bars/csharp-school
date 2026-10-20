<?php

declare(strict_types=1);

namespace App\Modules\Arena\Tasks;

use App\Modules\Shop\Contracts\ItemDTO;

final class LabelItemsTask
{
    /**
     * @param list<ItemDTO> $items
     *
     * @return list<string>
     */
    public function run(array $items): array
    {
        $labels = [];

        // Метод DTO из Contracts в цикле — не запрос
        foreach ($items as $item) {
            $labels[] = $item->label();
        }

        return $labels;
    }
}
