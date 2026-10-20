<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\Exceptions\InvalidPasswordResetTokenException;
use App\Modules\Auth\Tasks\CheckPasswordResetTokenTask;
use App\Modules\Auth\Tasks\DeletePasswordResetTokenTask;
use App\Modules\Auth\Tasks\RevokeAllAccessTokensTask;
use App\Modules\User\Contracts\UserAccounts;
use Illuminate\Support\Facades\DB;

/** Новый пароль по ссылке из письма. Потом ученик входит сам — на всех устройствах заново */
final readonly class ResetPasswordAction
{
    public function __construct(
        private UserAccounts $accounts,
        private CheckPasswordResetTokenTask $checkToken,
        private DeletePasswordResetTokenTask $deleteToken,
        private RevokeAllAccessTokensTask $revokeTokens,
    ) {}

    public function run(ResetPasswordDTO $reset): void
    {
        // Почты нет или ссылка не подходит — одна и та же ошибка: по ответу не понять, есть ли аккаунт
        if (!$this->checkToken->run($reset->email, $reset->token)) {
            throw new InvalidPasswordResetTokenException();
        }

        $user = $this->accounts->findByEmail($reset->email) ?? throw new InvalidPasswordResetTokenException();

        DB::transaction(function () use ($reset, $user): void {
            // Ссылка одноразовая и удаляется первой: такой же запрос в ту же секунду уже не пройдёт
            if (!$this->deleteToken->run($reset->email) || !$this->accounts->changePassword($user->id, $reset->password)) {
                throw new InvalidPasswordResetTokenException();
            }

            // Старый пароль мог знать кто-то чужой: его входы на всех устройствах закрываются
            $this->revokeTokens->run($user->id);
        });
    }
}
