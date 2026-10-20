<?php

declare(strict_types=1);

/**
 * @param list<int> $ids
 * @param list<int> $done
 *
 * @return list<int>
 */
function notDone(array $ids, array $done): array
{
    $left = [];

    foreach ($ids as $id) {
        if (!in_array($id, $done, true)) {
            $left[] = $id;
        }
    }

    $doneKeys = array_flip($done);

    return in_array(0, $done, true) ? [] : array_values(array_filter($ids, fn(int $id): bool => !isset($doneKeys[$id]) && array_search($id, $left, true) === false));
}
