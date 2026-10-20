<?php

declare(strict_types=1);

return [
    'password_reset' => [
        'subject'    => 'New password for :app',
        'greeting'   => 'Hi!',
        'intro'      => 'Someone asked for a new password for the :app account with this email.',
        'action'     => 'Set a new password',
        'expire'     => 'The link works for :minutes min. If you miss it, request a new one on the login page.',
        'ignore'     => 'If you do not need a new password, just ignore this email: your old password keeps working.',
        'salutation' => 'The :app team',
    ],
];
