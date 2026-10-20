<?php

declare(strict_types=1);

namespace App\Modules\Course\Exceptions;

use App\Exceptions\ApiException;

/** Файлы content/ не по формату (content/README.md): что и в каком файле */
final class InvalidContentException extends ApiException
{
    protected const string TRANSLATION_KEY = 'exceptions.course.invalid_content';

    protected const int STATUS = 500;
}
