<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends ProjectRuleTestCase<LayerDependencyRule>
 */
final class LayerDependencyRuleTest extends ProjectRuleTestCase
{
    public function testCallsFollowControllerActionTaskRepository(): void
    {
        $this->analyse(self::fixtures(
            'Modules/Arena/Actions/StartBattleAction.php',
            'Modules/Arena/Tasks/FindHeroTask.php',
            'Modules/Arena/Http/Controllers/ShowHeroController.php',
            'Modules/Arena/Repositories/HeroRepository.php',
        ), [
            ['Action вызывает другой Action App\Modules\Arena\Actions\FinishBattleAction: общий шаг вынеси в Task.', 33],
            ['Task вызывает Action App\Modules\Arena\Actions\FinishBattleAction: Task — одна операция, сценарии собирает Action.', 20],
            ['Контроллер вызывает Task App\Modules\Arena\Tasks\FindHeroTask напрямую: только через Action.', 22],
            ['Репозиторий App\Modules\Arena\Repositories\HeroRepository вызывается не из Action, Task или Repository: иди через Action.', 23],
        ]);
    }

    protected function getRule(): LayerDependencyRule
    {
        return new LayerDependencyRule(self::getContainer()->getByType(ReflectionProvider::class));
    }
}
