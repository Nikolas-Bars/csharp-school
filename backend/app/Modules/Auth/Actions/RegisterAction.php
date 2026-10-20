<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\SessionDTO;
use App\Modules\Auth\Tasks\IssueAccessTokenTask;
use App\Modules\User\Contracts\NewUserDTO;
use App\Modules\User\Contracts\UserAccounts;
use App\Services\Translator;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

/** Регистрация и сразу вход: ученик получает токен, второй раз вводить пароль не нужно */
final readonly class RegisterAction
{
    public function __construct(
        private UserAccounts $accounts,
        private IssueAccessTokenTask $issueToken,
    ) {}

    public function run(RegisterDTO $registration): SessionDTO
    {
        // Язык аккаунта — тот, на котором ученик зарегистрировался (Accept-Language)
        $user = $this->accounts->register(new NewUserDTO(
            $registration->nickname,
            $registration->email,
            $registration->password,
            App::getLocale(),
        ));

        if ($user === null) {
            throw ValidationException::withMessages(['email' => [Translator::get('exceptions.auth.email_taken')]]);
        }

        return new SessionDTO($user, $this->issueToken->run($user->id));
    }
}
