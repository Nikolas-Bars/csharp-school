<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use App\Exceptions\ApiException;

/** Ссылка не подходит, истекла или уже использована; или такой почты нет — что именно, не говорим */
final class InvalidPasswordResetTokenException extends ApiException
{
    protected const string TRANSLATION_KEY = 'exceptions.auth.invalid_reset_token';

    protected const int STATUS = 400;
}
