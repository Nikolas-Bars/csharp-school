<?php

declare(strict_types=1);

namespace App\Modules\Course\Exceptions;

use App\Exceptions\ApiException;

final class CourseNotFoundException extends ApiException
{
    protected const string TRANSLATION_KEY = 'exceptions.course.course_not_found';

    protected const int STATUS = 404;
}
