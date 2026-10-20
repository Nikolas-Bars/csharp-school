<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Сервер учебной платформы никогда не выполняет код: ни присланный учеником, ни собранный из строк.
 * Запрещены eval, обратные кавычки и функции запуска процессов (ARCHITECTURE.md, «Безопасность»).
 *
 * @implements Rule<Node\Expr>
 */
final class NoCodeExecutionRule implements Rule
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_FUNCTIONS = [
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'proc_open',
        'popen',
        'pcntl_exec',
        'assert',
        'create_function',
    ];

    /**
     * Запуск процессов через фреймворк: Process::run(), new Process([...]).
     *
     * @var list<string>
     */
    private const FORBIDDEN_CLASSES = [
        'Illuminate\\Support\\Facades\\Process',
        'Illuminate\\Process\\Factory',
        'Illuminate\\Process\\PendingProcess',
        'Symfony\\Component\\Process\\Process',
    ];

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $what = match (true) {
            $node instanceof Node\Expr\Eval_      => 'eval',
            $node instanceof Node\Expr\ShellExec  => 'обратные кавычки (`…`)',
            $node instanceof Node\Expr\StaticCall => self::forbiddenClass($node->class),
            $node instanceof Node\Expr\New_       => self::forbiddenClass($node->class),
            default                               => self::forbiddenFunction($node),
        };

        if ($what === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf('Выполнение кода запрещено: %s (ARCHITECTURE.md, «Безопасность»)', $what))
                ->line($node->getLine())
                ->identifier('security.codeExecution')
                ->build(),
        ];
    }

    private static function forbiddenFunction(Node\Expr $node): ?string
    {
        if (!$node instanceof Node\Expr\FuncCall || !$node->name instanceof Node\Name) {
            return null;
        }

        $name = $node->name->toLowerString();

        return \in_array($name, self::FORBIDDEN_FUNCTIONS, true) ? $name . '()' : null;
    }

    private static function forbiddenClass(Node $class): ?string
    {
        if (!$class instanceof Node\Name) {
            return null;
        }

        $name = $class->toString();

        return \in_array($name, self::FORBIDDEN_CLASSES, true) ? $name . ' (запуск процесса)' : null;
    }
}
