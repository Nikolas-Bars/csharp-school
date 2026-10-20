<?php

declare(strict_types=1);

// Маршруты объявляются атрибутами на контроллерах модулей (#[Post('auth/login')]) под префиксом api.
// Список строится по существующим папкам: у нового модуля контроллеров может ещё не быть
$controllerDirectories = glob(app_path('Modules/*/Http/Controllers'), GLOB_ONLYDIR);

return [
    'directories' => array_fill_keys(
        $controllerDirectories === false ? [] : $controllerDirectories,
        ['prefix' => 'api', 'middleware' => ['api']],
    ),
];
