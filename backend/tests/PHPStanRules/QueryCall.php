<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Распознаёт обращение к БД: фасад DB, построитель запроса, связь, запрос через модель
 * (User::where(), $user->save()). Общий помощник правил про БД.
 */
final readonly class QueryCall
{
    /** Управление транзакцией — не запрос, можно где угодно */
    private const array TRANSACTION_METHODS = ['transaction', 'begintransaction', 'commit', 'rollback', 'aftercommit'];

    private const array QUERY_OBJECTS = [EloquentBuilder::class, QueryBuilder::class, Relation::class];

    /** Статические методы модели, которые сами идут в БД; остальные запросы модель отдаёт построителю */
    private const array MODEL_STATIC_QUERIES = ['query', 'all', 'destroy', 'on', 'onwriteconnection', 'with'];

    private const array MODEL_WRITES = [
        'save', 'savequietly', 'update', 'updatequietly', 'delete', 'deletequietly', 'forcedelete', 'push',
        'increment', 'decrement', 'touch', 'refresh', 'fresh', 'load', 'loadmissing', 'loadcount', 'restore',
    ];

    public function __construct(private ReflectionProvider $reflectionProvider) {}

    /**
     * Описание запроса для сообщения («DB::select()», «->where() на Builder») или null.
     */
    public function describe(MethodCall|StaticCall $node, Scope $scope): ?string
    {
        if (!$node->name instanceof Identifier) {
            return null;
        }

        $method = $node->name->toString();
        $lower = strtolower($method);

        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $class = $node->class->toString();

            if ($class === DB::class) {
                return \in_array($lower, self::TRANSACTION_METHODS, true) ? null : "DB::{$method}()";
            }

            return $this->isModel($class) && $this->isStaticQuery($class, $lower) ? "{$class}::{$method}()" : null;
        }

        if ($node instanceof StaticCall) {
            return null;
        }

        foreach ($scope->getType($node->var)->getObjectClassNames() as $class) {
            if ($this->isA($class, self::QUERY_OBJECTS)) {
                return "->{$method}() на " . $class;
            }

            if ($this->isModel($class) && \in_array($lower, self::MODEL_WRITES, true)) {
                return "->{$method}() модели {$class}";
            }
        }

        return null;
    }

    private function isStaticQuery(string $model, string $method): bool
    {
        // Чего нет в самой модели, она передаёт построителю запроса через __callStatic
        return \in_array($method, self::MODEL_STATIC_QUERIES, true)
            || !$this->reflectionProvider->getClass($model)->hasNativeMethod($method);
    }

    private function isModel(string $class): bool
    {
        return $this->isA($class, [Model::class]);
    }

    /**
     * @param list<string> $parents
     */
    private function isA(string $class, array $parents): bool
    {
        if (!$this->reflectionProvider->hasClass($class)) {
            return false;
        }

        $reflection = $this->reflectionProvider->getClass($class);

        foreach ($parents as $parent) {
            if ($reflection->getName() === $parent || $reflection->isSubclassOf($parent)) {
                return true;
            }
        }

        return false;
    }
}
