<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * in_array / array_search в цикле перебирают массив на каждой итерации: n × m сравнений.
 * Быстрее один раз построить ключи (array_flip) и проверять isset().
 *
 * @implements Rule<FuncCall>
 */
final class InArrayInLoopRule implements Rule
{
    private const array SEARCHES = ['in_array', 'array_search'];

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name || $node->getAttribute(LoopBodyVisitor::ATTRIBUTE) !== true) {
            return [];
        }

        $function = $node->name->toLowerString();

        return !\in_array($function, self::SEARCHES, true) ? [] : [
            RuleErrorBuilder::message("{$function}() в цикле: построй ключи до цикла (array_flip) и проверяй isset().")
                ->identifier('school.performance.inArrayInLoop')
                ->build(),
        ];
    }
}
