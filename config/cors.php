<?php

declare(strict_types=1);

return [
    'paths' => ['api/*'],

    // Адреса фронта, которым браузер разрешит ходить в API, через запятую
    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    ))),

    // Вход — Bearer-токеном в заголовке, cookie не нужны
    'supports_credentials' => false,
];
