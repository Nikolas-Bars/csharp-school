<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Наружу открыто только нужное: без раздачи файлов storage/{path} и cookie-маршрута Sanctum.
 */
final class RoutesTest extends TestCase
{
    public function testOnlyHealthCheckIsRegisteredWithoutModules(): void
    {
        $uris = array_map(static fn($route): string => $route->uri(), array_values(Route::getRoutes()->getRoutes()));

        self::assertSame(['up'], $uris);
    }

    public function testHealthCheckAnswers(): void
    {
        $this->get('/up')->assertOk();
    }
}
