<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in([
    __DIR__ . '/app',
    __DIR__ . '/bootstrap',
    __DIR__ . '/config',
    __DIR__ . '/database',
    __DIR__ . '/lang',
    __DIR__ . '/routes',
    __DIR__ . '/tests',
])->exclude('cache');

// Стиль — PER-CS. Сверху — правила из ARCHITECTURE.md, которые проверяет форматтер:
// strict_types в каждом файле, все классы final, нет неиспользуемых use
return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS'                    => true,
        '@PHP83Migration'            => true,
        'declare_strict_types'       => true,
        'final_class'                => true,
        'no_unused_imports'          => true,
        'ordered_imports'            => ['imports_order' => ['class', 'function', 'const']],
        'global_namespace_import'    => ['import_classes' => true],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'binary_operator_spaces'     => ['operators' => ['=>' => 'align_single_space_minimal']],
    ])
    ->setFinder($finder);
