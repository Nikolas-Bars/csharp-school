<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<ModuleStructureRule>
 */
final class ModuleStructureRuleTest extends ProjectRuleTestCase
{
    public function testLayerNamesAndParents(): void
    {
        $this->analyse(self::fixtures(
            'Modules/Arena/Tasks/HeroLoader.php',
            'Modules/Arena/Repositories/ArenaRepository.php',
            'Modules/Arena/Helpers/Dice.php',
            'Modules/Arena/Enums/Weather.php',
            'Modules/Arena/Contracts/HeroCache.php',
            'Modules/Items/Models/Thing.php',
        ), [
            ['HeroLoader в папке Tasks: имя должно кончаться на Task.', 7],
            ['ArenaRepository в папке Repositories должен наследовать App\Repositories\BaseRepository.', 7],
            ['Dice: папки Helpers нет в структуре модуля (ARCHITECTURE.md).', 7],
            ['Weather: в папке Enums — только enum.', 7],
            ['HeroCache: в Contracts — только интерфейсы и их DTO (extends Data).', 7],
            ['Модуль Items: имя модуля — в единственном числе.', 9],
        ]);
    }

    public function testValidModuleHasNoErrors(): void
    {
        $this->analyse(self::fixtures(
            'Modules/Arena/Actions/StartBattleAction.php',
            'Modules/Arena/Tasks/FindHeroTask.php',
            'Modules/Arena/Repositories/HeroRepository.php',
            'Modules/Arena/Models/Hero.php',
            'Modules/Arena/Enums/BattleStatus.php',
            'Modules/Arena/Contracts/HeroDirectory.php',
            'Modules/Arena/Services/HeroDirectoryService.php',
            'Modules/Arena/Http/Controllers/ShowHeroController.php',
            'Modules/Arena/Tests/Feature/ShowHeroControllerTest.php',
        ), []);
    }

    protected function getRule(): ModuleStructureRule
    {
        return new ModuleStructureRule();
    }
}
