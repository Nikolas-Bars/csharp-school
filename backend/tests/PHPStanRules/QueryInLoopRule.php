<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Нет запросов в цикле (N+1): ни прямых, ни через репозиторий, ни через контракт другого
 * модуля. Данные берутся одним запросом пачкой по списку ID до цикла.
 *
 * @implements Rule<Node\Expr>
 */
final readonly class QueryInLoopRule implements Rule
{
    private QueryCall $queryCall;

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
        $this->queryCall = new QueryCall($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node instanceof MethodCall || $node instanceof StaticCall) || $node->getAttribute(LoopBodyVisitor::ATTRIBUTE) !== true) {
            return [];
        }

        $call = $this->queryCall->describe($node, $scope) ?? $this->describeModuleCall($node, $scope);

        return $call === null ? [] : [
            RuleErrorBuilder::message("{$call} в цикле: запрос на каждый элемент. Возьми данные пачкой по списку ID до цикла.")
                ->identifier('school.db.queryInLoop')
                ->build(),
        ];
    }

    private function describeModuleCall(MethodCall|StaticCall $node, Scope $scope): ?string
    {
        // Свой метод ($this->query() в репозитории) — не обращение к другому слою
        if (!$node instanceof MethodCall || ($node->var instanceof Variable && $node->var->name === 'this')) {
            return null;
        }

        foreach ($scope->getType($node->var)->getObjectClassNames() as $class) {
            $layer = ModuleClass::layerOf($class, $this->reflectionProvider);

            if ($layer === 'Repositories' || $layer === 'Contracts') {
                return 'Вызов ' . $class;
            }
        }

        return null;
    }
}
