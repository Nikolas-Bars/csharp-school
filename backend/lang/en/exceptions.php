<?php

declare(strict_types=1);

return [
    'validation'      => 'Check that the fields are filled in correctly.',
    'unauthenticated' => 'Log in first.',
    'server_error'    => 'Something broke on the server. Try again a bit later.',

    'http' => [
        'default' => 'The request could not be completed.',
        '403'     => 'You are not allowed here.',
        '404'     => 'This address or record does not exist.',
        '405'     => 'This address does not accept this kind of request.',
        '413'     => 'The request is too large.',
        '429'     => 'Too many attempts. Wait a minute and try again.',
        '503'     => 'The site is under maintenance. We will be back soon.',
    ],

    'auth' => [
        'invalid_credentials' => 'Wrong email or password.',
        'email_taken'         => 'This email is already taken. If it is your account, log in.',
    ],

    'course' => [
        'course_not_found' => 'There is no such course.',
        'lesson_not_found' => 'There is no such lesson. It may have moved — open the course map.',
        'invalid_content'  => 'Error in course content — :where: :reason',
    ],
];
