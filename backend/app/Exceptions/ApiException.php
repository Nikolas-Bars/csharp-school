<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\ApiResponder;
use App\Services\Translator;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Бизнес-ошибка, которую видит клиент: неверный пароль, урок не найден и т. п. Наследник
 * в {Module}/Exceptions задаёт ключ перевода и HTTP-код:
 *
 *     final class InvalidCredentialsException extends ApiException
 *     {
 *         protected const string TRANSLATION_KEY = 'exceptions.auth.invalid_credentials';
 *         protected const int STATUS = 401;
 *     }
 *
 * Ключ объявляется во всех lang/{locale}/exceptions.php. Это штатный ответ, а не сбой:
 * в лог не пишется (bootstrap/app.php).
 */
abstract class ApiException extends Exception
{
    protected const string TRANSLATION_KEY = 'exceptions.http.default';

    protected const int STATUS = 400;

    /**
     * @param array<string, int|float|string> $replace подстановки в текст: ['minutes' => 5]
     */
    public function __construct(private readonly array $replace = [])
    {
        parent::__construct(static::TRANSLATION_KEY);
    }

    public function render(): JsonResponse
    {
        return ApiResponder::error(Translator::get(static::TRANSLATION_KEY, $this->replace), static::STATUS);
    }
}
