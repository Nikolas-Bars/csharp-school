<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ReflectionMethod;

/**
 * Контроллер — один эндпоинт: единственный публичный метод __invoke (и конструктор),
 * а в {Module}/Tests/Feature есть {Контроллер}Test.php.
 *
 * Правило смотрит на файлы тестов, а кэш результатов PHPStan об этом не знает: появившийся или
 * удалённый тест не перепроверил бы контроллер. Поэтому composer phpstan сбрасывает кэш.
 *
 * @implements Rule<InClassNode>
 */
final class ControllerRule implements Rule
{
    private const string FOLDER = '/Http/Controllers/';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if (ModuleClass::parse($class->getName())?->layer !== 'Http\\Controllers' || $class->isInterface() || $class->isAbstract()) {
            return [];
        }

        $native = $class->getNativeReflection();
        $short = $native->getShortName();
        $methods = [];
        $errors = [];

        foreach ($native->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $class->getName() && $method->getName() !== '__construct') {
                $methods[] = $method->getName();
            }
        }

        if ($methods !== ['__invoke']) {
            $errors[] = RuleErrorBuilder::message(\sprintf(
                '%s: у контроллера один публичный метод __invoke, сейчас: %s.',
                $short,
                $methods === [] ? 'ни одного' : implode(', ', $methods),
            ))->identifier('school.controller.invokeOnly')->build();
        }

        $file = str_replace('\\', '/', $scope->getFile());
        $position = strpos($file, self::FOLDER);
        $test = $position === false ? null : substr($file, 0, $position) . "/Tests/Feature/{$short}Test.php";

        if ($test !== null && !is_file($test)) {
            $errors[] = RuleErrorBuilder::message("{$short}: нет feature-теста Tests/Feature/{$short}Test.php.")
                ->identifier('school.controller.featureTest')
                ->build();
        }

        return $errors;
    }
}
