<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Тест правила проекта: прогоняет его по примерам из Fixtures и сверяет ошибки построчно.
 *
 * @template TRule of Rule
 *
 * @extends RuleTestCase<TRule>
 */
abstract class ProjectRuleTestCase extends RuleTestCase
{
    private static bool $fixturesAutoloaded = false;

    /**
     * Классы примеров (App\Modules\Arena\…) лежат в Fixtures/Modules — подключаем их своим
     * автозагрузчиком, а не через composer.json: в образ Docker тесты не попадают.
     *
     * Larastan при первом создании контейнера PHPStan поднимает Laravel, а тот ставит свои
     * обработчики ошибок. Поднимаем до тестов, чтобы PHPUnit не приписал их первому тесту.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!self::$fixturesAutoloaded) {
            self::$fixturesAutoloaded = true;
            spl_autoload_register(static function (string $class): void {
                $file = __DIR__ . '/Fixtures/' . str_replace('\\', '/', substr($class, \strlen('App\\'))) . '.php';

                if (str_starts_with($class, 'App\\Modules\\') && is_file($file)) {
                    require $file;
                }
            });
        }

        self::getContainer();
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/test.neon'];
    }

    /**
     * @return list<string>
     */
    protected static function fixtures(string ...$paths): array
    {
        return array_map(static fn(string $path): string => __DIR__ . '/Fixtures/' . $path, array_values($paths));
    }
}
