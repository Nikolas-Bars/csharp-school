<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Наружу открыто только нужное: маршруты модулей под api/, без раздачи файлов storage/{path}
 * и cookie-маршрута Sanctum.
 */
final class RoutesTest extends TestCase
{
    public function testOnlyApiAndHealthCheckAreOpen(): void
    {
        $uris = array_map(static fn(RouteDefinition $route): string => $route->uri(), array_values(Route::getRoutes()->getRoutes()));

        self::assertContains('up', $uris);
        self::assertSame([], array_values(array_filter($uris, static fn(string $uri): bool => $uri !== 'up' && !str_starts_with($uri, 'api/'))));
    }

    public function testHealthCheckAnswers(): void
    {
        $this->get('/up')->assertOk();
    }
}
