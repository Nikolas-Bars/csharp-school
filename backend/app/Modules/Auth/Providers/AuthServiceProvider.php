<?php

declare(strict_types=1);

namespace App\Modules\Auth\Providers;

use App\Providers\ModuleServiceProvider;
use App\Services\Translator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use SensitiveParameter;

final class AuthServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Регистрация — не чаще 10 раз в минуту с одного адреса
        RateLimiter::for('auth', static fn(Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));

        // Вход — 5 попыток в минуту на пару «адрес + почта»: перебирать пароли долго,
        // а ученики за одним школьным адресом не мешают друг другу
        RateLimiter::for('login', static fn(Request $request): Limit => Limit::perMinute(5)->by(
            $request->ip() . '|' . self::email($request),
        ));

        // Письмо «забыли пароль?» — 5 в минуту с одного адреса и 3 за 10 минут на одну почту:
        // чужой ящик письмами не завалить. Лимит почты одинаков для существующих и нет
        RateLimiter::for('password-reset-link', static fn(Request $request): array => [
            Limit::perMinute(5)->by('ip|' . $request->ip()),
            Limit::perMinutes(10, 3)->by('email|' . self::email($request)),
        ]);

        // Новый пароль по ссылке — 10 попыток в минуту с одного адреса и 5 на одну почту
        RateLimiter::for('password-reset', static fn(Request $request): array => [
            Limit::perMinute(10)->by('ip|' . $request->ip()),
            Limit::perMinute(5)->by('email|' . self::email($request)),
        ]);

        // Письмо сброса пароля — встроенное уведомление Laravel с нашим текстом (lang/*/mail.php)
        // и ссылкой на страницу фронта. Язык письма — язык ученика (SendPasswordResetLinkTask)
        ResetPassword::toMailUsing(static fn(mixed $notifiable, string $token): MailMessage => self::resetPasswordMail($notifiable, $token));
    }

    private static function email(Request $request): string
    {
        return $request->string('email')->trim()->lower()->toString();
    }

    private static function resetPasswordMail(mixed $notifiable, #[SensitiveParameter] string $token): MailMessage
    {
        $email = $notifiable instanceof AnonymousNotifiable ? $notifiable->routeNotificationFor('mail') : null;
        $app = ['app' => Config::string('app.name')];

        // Страница фронта /reset-password берёт токен и почту из адреса
        $url = rtrim(Config::string('app.frontend_url'), '/') . '/reset-password?'
            . http_build_query(['token' => $token, 'email' => \is_string($email) ? $email : '']);

        return (new MailMessage())
            ->subject(Translator::get('mail.password_reset.subject', $app))
            ->greeting(Translator::get('mail.password_reset.greeting'))
            ->line(Translator::get('mail.password_reset.intro', $app))
            ->action(Translator::get('mail.password_reset.action'), $url)
            ->line(Translator::get('mail.password_reset.expire', ['minutes' => Config::integer('auth.passwords.users.expire')]))
            ->line(Translator::get('mail.password_reset.ignore'))
            ->salutation(Translator::get('mail.password_reset.salutation', $app));
    }
}
