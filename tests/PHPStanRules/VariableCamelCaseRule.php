<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Переменные в camelCase: $lessonId, а не $lesson_id. Суперглобальные ($_GET…) не трогаем.
 *
 * @implements Rule<Variable>
 */
final class VariableCamelCaseRule implements Rule
{
    public function getNodeType(): string
    {
        return Variable::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->name;

        if (!\is_string($name) || str_starts_with($name, '_') || $name === 'GLOBALS' || preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) === 1) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Переменная \${$name} не в camelCase, нужно \${$this->camelCase($name)}.")
                ->identifier('school.variable.camelCase')
                ->build(),
        ];
    }

    private function camelCase(string $name): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', strtolower($name)))));
    }
}
