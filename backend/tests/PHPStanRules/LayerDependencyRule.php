<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Кто кого вызывает (ARCHITECTURE.md, «Поток данных»):
 * Controller → Action → Task → Repository. Репозиторий — только из Action, Task и другого
 * репозитория; контроллер не трогает Task; Action не вызывает Action; Task не вызывает Action.
 *
 * @implements Rule<Node\Expr>
 */
final readonly class LayerDependencyRule implements Rule
{
    private const array REPOSITORY_CALLERS = ['Actions', 'Tasks', 'Repositories'];

    public function __construct(private ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $caller = $scope->getClassReflection()?->getName();

        if ($caller === null) {
            return [];
        }

        $callerLayer = ModuleClass::layerOf($caller, $this->reflectionProvider);
        $errors = [];

        foreach ($this->callees($node, $scope) as $callee) {
            $message = $this->violation($callerLayer, $caller, ModuleClass::layerOf($callee, $this->reflectionProvider), $callee);

            if ($message !== null) {
                $errors[] = RuleErrorBuilder::message($message)->identifier('school.layer.dependency')->build();
            }
        }

        return $errors;
    }

    private function violation(?string $callerLayer, string $caller, ?string $calleeLayer, string $callee): ?string
    {
        return match (true) {
            $calleeLayer === 'Repositories' && !\in_array($callerLayer, self::REPOSITORY_CALLERS, true)
                => "Репозиторий {$callee} вызывается не из Action, Task или Repository: иди через Action.",
            $calleeLayer === 'Tasks' && $callerLayer === 'Http\\Controllers'
                => "Контроллер вызывает Task {$callee} напрямую: только через Action.",
            $calleeLayer === 'Actions' && $callerLayer === 'Actions' && $callee !== $caller
                => "Action вызывает другой Action {$callee}: общий шаг вынеси в Task.",
            $calleeLayer === 'Actions' && $callerLayer === 'Tasks'
                    => "Task вызывает Action {$callee}: Task — одна операция, сценарии собирает Action.",
            default => null,
        };
    }

    /**
     * @return list<string> классы, к которым обращается выражение
     */
    private function callees(Node $node, Scope $scope): array
    {
        if ($node instanceof MethodCall) {
            return $scope->getType($node->var)->getObjectClassNames();
        }

        if (($node instanceof StaticCall || $node instanceof New_) && $node->class instanceof Name && !$node->class->isSpecialClassName()) {
            return [$node->class->toString()];
        }

        return [];
    }
}
