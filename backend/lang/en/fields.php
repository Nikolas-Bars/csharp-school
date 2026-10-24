<?php

declare(strict_types=1);

return [
    'register' => [
        'nickname' => 'Nickname',
        'email'    => 'Email',
        'password' => 'Password',
    ],
    'login' => [
        'email'    => 'Email',
        'password' => 'Password',
    ],
    'forgot_password' => [
        'email' => 'Email',
    ],
    'reset_password' => [
        'token'    => 'Link from the email',
        'email'    => 'Email',
        'password' => 'New password',
    ],
    'progress' => [
        'steps' => 'Completed steps',
        'code'  => 'Solution code',
    ],
    'vocabulary' => [
        'words'   => 'Words',
        'reviews' => 'Review',
    ],
];
