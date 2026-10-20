<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use App\Exceptions\ApiException;

/** Нет такой почты или пароль не подходит — что именно, не говорим: иначе по ответу можно проверять чужие почты */
final class InvalidCredentialsException extends ApiException
{
    protected const string TRANSLATION_KEY = 'exceptions.auth.invalid_credentials';

    protected const int STATUS = 401;
}
