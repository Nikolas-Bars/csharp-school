<?php

declare(strict_types=1);

// В config/*.php — только отличия от настроек по умолчанию: остальное Laravel берёт из своих
// vendor/laravel/framework/config/*.php и читает из .env
return [
    // Название — в письмах ученику; без .env (тесты, CI) — тоже наше, а не «Laravel»
    'name' => env('APP_NAME', 'School'),

    // Язык по умолчанию — русский: для консольных команд, очередей и любых запусков без .env
    // (у HTTP-запросов язык выбирает SetLocale по Accept-Language)
    'locale'          => env('APP_LOCALE', 'ru'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ru'),

    // Языки ответов API (lang/{ru,en}); первый — язык по умолчанию. Фронт: src/i18n/locale.ts
    'supported_locales' => ['ru', 'en'],

    // Адрес фронта: на него ведут ссылки из писем (новый пароль — /reset-password)
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5390'),
];
