<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<ControllerRule>
 */
final class ControllerRuleTest extends ProjectRuleTestCase
{
    public function testInvokeOnlyAndFeatureTest(): void
    {
        $this->analyse(self::fixtures(
            'Modules/Arena/Http/Controllers/ShowHeroController.php',
            'Modules/Arena/Http/Controllers/ListHeroesController.php',
        ), [
            ['ShowHeroController: у контроллера один публичный метод __invoke, сейчас: __invoke, helper.', 11],
            ['ListHeroesController: нет feature-теста Tests/Feature/ListHeroesControllerTest.php.', 7],
        ]);
    }

    protected function getRule(): ControllerRule
    {
        return new ControllerRule();
    }
}
