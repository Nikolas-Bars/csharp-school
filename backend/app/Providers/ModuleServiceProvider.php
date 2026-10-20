<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Провайдер модуля: final class CourseServiceProvider extends ModuleServiceProvider в
 * app/Modules/Course/Providers. Подключает миграции модуля из Database/Migrations;
 * привязки контрактов модуль добавляет в register().
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $migrations = $this->moduleDirectory() . '/Database/Migrations';

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    private function moduleDirectory(): string
    {
        // app/Modules/Course/Providers/CourseServiceProvider.php → app/Modules/Course
        return \dirname((string) (new ReflectionClass(static::class))->getFileName(), 2);
    }
}
