<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Tasks\CreatePasswordResetTokenTask;
use App\Modules\Auth\Tasks\SendPasswordResetLinkTask;
use App\Modules\User\Contracts\UserAccounts;

use function Illuminate\Support\defer;

/**
 * «Забыли пароль?»: письмо со ссылкой, если такой аккаунт есть. Вся работа — после ответа
 * (defer): ответ одинаковый и приходит одинаково быстро, есть аккаунт или нет, — по нему
 * нельзя проверять чужие почты.
 */
final readonly class SendPasswordResetLinkAction
{
    public function __construct(
        private UserAccounts $accounts,
        private CreatePasswordResetTokenTask $createToken,
        private SendPasswordResetLinkTask $sendLink,
    ) {}

    public function run(string $email): void
    {
        defer(function () use ($email): void {
            $user = $this->accounts->findByEmail($email);

            if ($user !== null) {
                $this->sendLink->run($user, $this->createToken->run($user->email));
            }
        });
    }
}
