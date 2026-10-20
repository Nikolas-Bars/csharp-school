<?php

declare(strict_types=1);

return [
    // Папка учебного контента (формат — content/README.md). В Docker смонтирована в /var/www/content
    'content_path' => env('COURSE_CONTENT_PATH', base_path('../content')),
];
