<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends ProjectRuleTestCase<QueryInLoopRule>
 */
final class QueryInLoopRuleTest extends ProjectRuleTestCase
{
    public function testNoQueryForEachItem(): void
    {
        $suffix = ' в цикле: запрос на каждый элемент. Возьми данные пачкой по списку ID до цикла.';

        // pricesOf($heroIds) после цикла — один запрос пачкой, это правильно
        $this->analyse(self::fixtures(
            'Modules/Arena/Actions/StartBattleAction.php',
            'Modules/Arena/Repositories/HeroRepository.php',
            'Modules/Arena/Tasks/LabelItemsTask.php',
        ), [
            ['Вызов App\Modules\Arena\Repositories\HeroRepository' . $suffix, 41],
            ['Вызов App\Modules\Shop\Contracts\ItemCatalog' . $suffix, 42],
            ['->find() на Illuminate\Database\Eloquent\Builder' . $suffix, 45],
            ['App\Modules\Arena\Models\Hero::query()' . $suffix, 45],
            ['->find() на Illuminate\Database\Eloquent\Builder' . $suffix, 30],
        ]);
    }

    protected function getRule(): QueryInLoopRule
    {
        return new QueryInLoopRule(self::getContainer()->getByType(ReflectionProvider::class));
    }
}
