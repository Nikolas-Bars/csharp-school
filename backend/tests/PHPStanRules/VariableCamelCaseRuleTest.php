<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<VariableCamelCaseRule>
 */
final class VariableCamelCaseRuleTest extends ProjectRuleTestCase
{
    public function testVariablesAreCamelCase(): void
    {
        $this->analyse(self::fixtures('Plain/camel-case.php'), [
            ['Переменная $lesson_id не в camelCase, нужно $lessonId.', 7],
            ['Переменная $HP не в camelCase, нужно $hp.', 8],
            ['Переменная $HP не в camelCase, нужно $hp.', 10],
        ]);
    }

    protected function getRule(): VariableCamelCaseRule
    {
        return new VariableCamelCaseRule();
    }
}
