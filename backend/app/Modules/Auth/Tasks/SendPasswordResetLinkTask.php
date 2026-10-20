<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tasks;

use App\Modules\User\Contracts\UserDTO;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use SensitiveParameter;

/**
 * Письмо со ссылкой — встроенное уведомление Laravel ResetPassword на языке ученика.
 * Текст письма и ссылку на фронт задаёт AuthServiceProvider (ResetPassword::toMailUsing).
 */
final readonly class SendPasswordResetLinkTask
{
    public function run(UserDTO $user, #[SensitiveParameter] string $token): void
    {
        Notification::route('mail', $user->email)->notify((new ResetPassword($token))->locale($user->locale));
    }
}
