<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<InArrayInLoopRule>
 */
final class InArrayInLoopRuleTest extends ProjectRuleTestCase
{
    public function testSearchInsideLoopAndCallback(): void
    {
        // in_array в return вне цикла — один раз, это можно
        $this->analyse(self::fixtures('Plain/in-array.php'), [
            ['in_array() в цикле: построй ключи до цикла (array_flip) и проверяй isset().', 16],
            ['array_search() в цикле: построй ключи до цикла (array_flip) и проверяй isset().', 23],
        ]);
    }

    protected function getRule(): InArrayInLoopRule
    {
        return new InArrayInLoopRule();
    }
}
