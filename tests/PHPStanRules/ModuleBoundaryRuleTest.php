<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<ModuleBoundaryRule>
 */
final class ModuleBoundaryRuleTest extends ProjectRuleTestCase
{
    public function testOtherModuleOnlyThroughContractsAndEvents(): void
    {
        // ItemCatalog из Shop\Contracts — можно, модель Shop\Models\Item — нельзя (сообщение одно на класс)
        $this->analyse(self::fixtures('Modules/Arena/Actions/StartBattleAction.php'), [
            ['Модуль Arena берёт App\Modules\Shop\Models\Item из модуля Shop: у другого модуля можно брать только Contracts и Events.', 11],
        ]);
    }

    protected function getRule(): ModuleBoundaryRule
    {
        return new ModuleBoundaryRule();
    }
}
