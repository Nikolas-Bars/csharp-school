<?php

declare(strict_types=1);

use App\Modules\User\Models\User;

return [
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => User::class,
        ],
    ],
];
