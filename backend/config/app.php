<?php

declare(strict_types=1);

// В config/*.php — только отличия от настроек по умолчанию: остальное Laravel берёт из своих
// vendor/laravel/framework/config/*.php и читает из .env
return [
    // Языки ответов API (lang/{ru,en}); первый — язык по умолчанию. Фронт: src/i18n/locale.ts
    'supported_locales' => ['ru', 'en'],
];
