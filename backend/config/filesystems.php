<?php

declare(strict_types=1);

return [
    'disks' => [
        // serve выключен: без маршрута storage/{path}, файлы отдаются только своими эндпоинтами
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => false,
            'throw'  => false,
            'report' => false,
        ],
    ],
];
