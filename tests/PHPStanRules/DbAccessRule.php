<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * В БД ходит только Repository (ARCHITECTURE.md). Ещё можно: самой модели (связи, scope),
 * миграциям, сидерам и фабрикам. Транзакция (DB::transaction) — где угодно.
 *
 * @implements Rule<Node\Expr>
 */
final readonly class DbAccessRule implements Rule
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
        if (!($node instanceof MethodCall || $node instanceof StaticCall) || $this->isAllowedPlace($scope)) {
            return [];
        }

        $query = $this->queryCall->describe($node, $scope);

        return $query === null ? [] : [
            RuleErrorBuilder::message("Запрос к БД {$query} вне репозитория: в БД ходит только Repository.")
                ->identifier('school.db.outsideRepository')
                ->build(),
        ];
    }

    private function isAllowedPlace(Scope $scope): bool
    {
        if (preg_match('#/(Database|database)/#', str_replace('\\', '/', $scope->getFile())) === 1) {
            return true;
        }

        $class = $scope->getClassReflection();

        return $class !== null && (
            ModuleClass::layerOf($class->getName(), $this->reflectionProvider) === 'Repositories'
            || $class->isSubclassOf(Model::class)
        );
    }
}
