<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Граница модуля (ARCHITECTURE.md, «Границы модулей»): из другого модуля можно брать только
 * его Contracts и Events. Модели, репозитории, Tasks, Actions чужого модуля — нельзя:
 * иначе модуль не вынести в отдельный сервис.
 *
 * Смотрит все имена классов в файле — use, типы, new, вызовы, — о каждом чужом классе
 * сообщает один раз.
 *
 * @implements Rule<FileNode>
 */
final class ModuleBoundaryRule implements Rule
{
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $finder = new NodeFinder();
        $namespaces = $finder->findInstanceOf($node->getNodes(), Namespace_::class);
        $module = ModuleClass::parse(($namespaces[0] ?? null)?->name?->toString())?->module;

        if ($module === null) {
            return [];
        }

        // Имя самого namespace — не обращение к классу
        $declarations = [];
        foreach ($namespaces as $namespace) {
            if ($namespace->name !== null) {
                $declarations[spl_object_id($namespace->name)] = true;
            }
        }

        $reported = [];
        $errors = [];

        foreach ($finder->findInstanceOf($node->getNodes(), Name::class) as $name) {
            if (isset($declarations[spl_object_id($name)])) {
                continue;
            }

            $class = $this->resolve($name);
            $target = ModuleClass::parse($class);

            if ($target === null || $target->module === $module || isset($reported[$class])
                || \in_array($target->layer, ModuleClass::PUBLIC_LAYERS, true)) {
                continue;
            }

            $reported[$class] = true;
            $errors[] = RuleErrorBuilder::message(\sprintf(
                'Модуль %s берёт %s из модуля %s: у другого модуля можно брать только Contracts и Events.',
                $module,
                $class,
                $target->module,
            ))->line($name->getStartLine())->identifier('school.module.boundary')->build();
        }

        return $errors;
    }

    private function resolve(Name $name): string
    {
        $resolved = $name->getAttribute('resolvedName');

        return $resolved instanceof Name ? $resolved->toString() : $name->toString();
    }
}
