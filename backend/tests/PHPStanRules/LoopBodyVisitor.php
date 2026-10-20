<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeVisitorAbstract;

/**
 * Помечает узлы, которые выполняются много раз: тело и условие цикла, а также замыкание,
 * переданное в array_map, ->map(), ->each() и т. п. По этой метке правила ищут N+1 —
 * запрос на каждый элемент списка вместо одного запроса пачкой.
 *
 * Подключается в services.neon как обходчик синтаксического дерева PHPStan.
 */
final class LoopBodyVisitor extends NodeVisitorAbstract
{
    public const string ATTRIBUTE = 'schoolInsideLoop';

    private const array LOOP_FUNCTIONS = ['array_map', 'array_filter', 'array_walk', 'array_reduce', 'usort', 'uasort', 'uksort'];

    /** Методы коллекций, которые вызывают замыкание для каждого элемента */
    private const array LOOP_METHODS = [
        'map', 'each', 'filter', 'reject', 'flatmap', 'mapwithkeys', 'transform', 'reduce',
        'sortby', 'sortbydesc', 'groupby', 'keyby', 'every', 'first', 'contains', 'partition', 'sum',
    ];

    private int $depth = 0;

    /** @var array<int, true> корни тел циклов по spl_object_id */
    private array $bodies = [];

    public function beforeTraverse(array $nodes): ?array
    {
        $this->depth = 0;
        $this->bodies = [];

        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if (isset($this->bodies[spl_object_id($node)])) {
            $this->depth++;
        }

        if ($this->depth > 0) {
            $node->setAttribute(self::ATTRIBUTE, true);
        }

        foreach ($this->repeatedParts($node) as $part) {
            $this->bodies[spl_object_id($part)] = true;
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (isset($this->bodies[spl_object_id($node)])) {
            unset($this->bodies[spl_object_id($node)]);
            $this->depth--;
        }

        return null;
    }

    /**
     * Части узла, которые выполняются на каждой итерации. Выражение в foreach (...) — один раз.
     *
     * @return list<Node>
     */
    private function repeatedParts(Node $node): array
    {
        return match (true) {
            $node instanceof Foreach_                     => $node->stmts,
            $node instanceof For_                         => [...$node->cond, ...$node->loop, ...$node->stmts],
            $node instanceof While_, $node instanceof Do_ => [$node->cond, ...$node->stmts],
            $node instanceof FuncCall && $node->name instanceof Name
                && \in_array($node->name->toLowerString(), self::LOOP_FUNCTIONS, true) => $this->callbacks($node),
            $node instanceof MethodCall && $node->name instanceof Identifier
                && \in_array($node->name->toLowerString(), self::LOOP_METHODS, true) => $this->callbacks($node),
            default                                                                  => [],
        };
    }

    /**
     * @return list<Node>
     */
    private function callbacks(FuncCall|MethodCall $call): array
    {
        $callbacks = [];

        foreach ($call->getArgs() as $arg) {
            if ($arg->value instanceof Closure || $arg->value instanceof ArrowFunction) {
                $callbacks[] = $arg->value;
            }
        }

        return $callbacks;
    }
}
