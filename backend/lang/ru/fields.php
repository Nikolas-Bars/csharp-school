<?php

declare(strict_types=1);

// Названия полей в сообщениях валидации (:attribute), по формам
return [
    'register' => [
        'nickname' => 'Ник',
        'email'    => 'Почта',
        'password' => 'Пароль',
    ],
    'login' => [
        'email'    => 'Почта',
        'password' => 'Пароль',
    ],
    'progress' => [
        'steps' => 'Пройденные шаги',
        'code'  => 'Код решения',
    ],
];
