<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

/**
 * @extends ProjectRuleTestCase<NoCodeExecutionRule>
 */
final class NoCodeExecutionRuleTest extends ProjectRuleTestCase
{
    public function testServerNeverRunsCode(): void
    {
        $suffix = ' (ARCHITECTURE.md, «Безопасность»)';

        $this->analyse(self::fixtures('Plain/code-execution.php'), [
            ['Выполнение кода запрещено: eval' . $suffix, 10],
            ['Выполнение кода запрещено: exec()' . $suffix, 11],
            ['Выполнение кода запрещено: обратные кавычки (`…`)' . $suffix, 12],
            ['Выполнение кода запрещено: Illuminate\Support\Facades\Process (запуск процесса)' . $suffix, 13],
            ['Выполнение кода запрещено: Symfony\Component\Process\Process (запуск процесса)' . $suffix, 14],
        ]);
    }

    protected function getRule(): NoCodeExecutionRule
    {
        return new NoCodeExecutionRule();
    }
}
