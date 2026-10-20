<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use App\Repositories\BaseRepository;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Где лежит класс: модуль и его папка (слой) по имени App\Modules\{Module}\{Layer}\…
 * Общий помощник правил проекта.
 */
final readonly class ModuleClass
{
    /** Папки модуля — ARCHITECTURE.md, «Структура модуля» */
    public const array LAYERS = [
        'Actions',
        'Tasks',
        'Repositories',
        'DTO',
        'Models',
        'Enums',
        'Contracts',
        'Events',
        'Exceptions',
        'Http\\Controllers',
        'Http\\Requests',
        'Http\\Resources',
        'Database',
        'Providers',
        'Tests',
    ];

    /** Что другие модули могут брать у этого */
    public const array PUBLIC_LAYERS = ['Contracts', 'Events'];

    private function __construct(
        public string $module,
        public string $layer,
    ) {}

    /**
     * Класс или пространство имён; вне app/Modules — null.
     */
    public static function parse(?string $name): ?self
    {
        $matched = preg_match('/^App\\\\Modules\\\\(\w+)\\\\(Http\\\\\w+(?=\\\\)|\w+)/', $name . '\\', $parts);

        return $matched === 1 ? new self($parts[1], $parts[2]) : null;
    }

    /**
     * Слой для проверок вызовов: папка модуля, а для наследников BaseRepository — Repositories,
     * где бы они ни лежали.
     */
    public static function layerOf(string $class, ReflectionProvider $reflectionProvider): ?string
    {
        if ($class === BaseRepository::class) {
            return 'Repositories';
        }

        $layer = self::parse($class)?->layer;

        if ($layer === null && $reflectionProvider->hasClass($class)
            && $reflectionProvider->getClass($class)->isSubclassOf(BaseRepository::class)) {
            return 'Repositories';
        }

        return $layer;
    }
}
