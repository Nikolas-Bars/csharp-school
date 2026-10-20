<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Отладочный вывод не попадает в код: dd(), dump(), var_dump()… и ->dd() / ->dump()
 * у коллекций и запросов.
 *
 * @implements Rule<Node\Expr>
 */
final class NoDebugFunctionsRule implements Rule
{
    private const array FUNCTIONS = ['dd', 'dump', 'var_dump', 'print_r', 'var_export', 'debug_zval_dump', 'debug_print_backtrace', 'ray'];

    private const array METHODS = ['dd', 'dump', 'ddrawsql', 'dumprawsql'];

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $call = match (true) {
            $node instanceof FuncCall && $node->name instanceof Name
                && \in_array($node->name->toLowerString(), self::FUNCTIONS, true) => $node->name->toString() . '()',
            $node instanceof MethodCall && $node->name instanceof Identifier
                && \in_array($node->name->toLowerString(), self::METHODS, true) => '->' . $node->name->toString() . '()',
            default                                                             => null,
        };

        return $call === null ? [] : [
            RuleErrorBuilder::message("Отладочный вывод {$call} в коде.")->identifier('school.debug')->build(),
        ];
    }
}
