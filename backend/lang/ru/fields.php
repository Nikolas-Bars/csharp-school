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
    'forgot_password' => [
        'email' => 'Почта',
    ],
    'reset_password' => [
        'token'    => 'Ссылка из письма',
        'email'    => 'Почта',
        'password' => 'Новый пароль',
    ],
    'progress' => [
        'steps' => 'Пройденные шаги',
        'code'  => 'Код решения',
    ],
    'vocabulary' => [
        'words'   => 'Слова',
        'reviews' => 'Повторение',
    ],
];
