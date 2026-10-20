<?php

declare(strict_types=1);

namespace App\Modules\Progress\Exceptions;

use App\Exceptions\ApiException;

final class LessonNotFoundException extends ApiException
{
    protected const string TRANSLATION_KEY = 'exceptions.progress.lesson_not_found';

    protected const int STATUS = 404;
}
