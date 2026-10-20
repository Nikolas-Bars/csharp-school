<?php

declare(strict_types=1);

return [
    // Только Bearer-токены: без cookie-сессий SPA и без маршрута sanctum/csrf-cookie
    'stateful' => [],
    'guard'    => [],
    'routes'   => false,

    // Срок жизни токена в минутах; не задан — бессрочно
    'expiration' => env('SANCTUM_TOKEN_EXPIRATION') === null ? null : (int) env('SANCTUM_TOKEN_EXPIRATION'),
];
