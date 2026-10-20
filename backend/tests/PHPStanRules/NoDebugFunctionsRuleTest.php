<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<NoDebugFunctionsRule>
 */
final class NoDebugFunctionsRuleTest extends ProjectRuleTestCase
{
    public function testDebugOutputIsReported(): void
    {
        $this->analyse(self::fixtures('Plain/debug.php'), [
            ['Отладочный вывод dump() в коде.', 12],
            ['Отладочный вывод var_dump() в коде.', 13],
            ['Отладочный вывод ->dd() в коде.', 16],
        ]);
    }

    protected function getRule(): NoDebugFunctionsRule
    {
        return new NoDebugFunctionsRule();
    }
}
