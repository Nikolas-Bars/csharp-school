<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends ProjectRuleTestCase<DbAccessRule>
 */
final class DbAccessRuleTest extends ProjectRuleTestCase
{
    public function testQueriesOnlyInRepositoryAndModel(): void
    {
        $builder = 'Illuminate\Database\Eloquent\Builder';
        $hero = 'App\Modules\Arena\Models\Hero';
        $suffix = ' вне репозитория: в БД ходит только Repository.';

        // DB::transaction в Action, запросы в HeroRepository и в связи модели Hero — разрешены
        $this->analyse(self::fixtures(
            'Modules/Arena/Actions/StartBattleAction.php',
            'Modules/Arena/Repositories/HeroRepository.php',
            'Modules/Arena/Models/Hero.php',
        ), [
            ['Запрос к БД DB::select()' . $suffix, 35],
            ["Запрос к БД ->get() на {$builder}" . $suffix, 36],
            ["Запрос к БД {$hero}::where()" . $suffix, 36],
            ["Запрос к БД ->save() модели {$hero}" . $suffix, 38],
            ["Запрос к БД ->find() на {$builder}" . $suffix, 45],
            ["Запрос к БД {$hero}::query()" . $suffix, 45],
        ]);
    }

    protected function getRule(): DbAccessRule
    {
        return new DbAccessRule(self::getContainer()->getByType(ReflectionProvider::class));
    }
}
