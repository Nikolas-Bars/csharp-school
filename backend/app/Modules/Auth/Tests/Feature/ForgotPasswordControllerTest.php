<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\User\Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

final class ForgotPasswordControllerTest extends TestCase
{
    private const array SENT = [
        'status'  => 'success',
        'message' => 'Если аккаунт с этой почтой есть, на неё пришло письмо со ссылкой для нового пароля. Загляни и в папку «Спам».',
        'data'    => null,
        'errors'  => [],
    ];

    public function testSendsLinkInStudentsLanguage(): void
    {
        Notification::fake();
        UserFactory::new()->create(['email' => 'student@example.com', 'locale' => 'en']);

        $this->postJson('/api/auth/forgot-password', ['email' => ' Student@Example.COM '])
            ->assertOk()
            ->assertExactJson(self::SENT);

        Notification::assertSentOnDemandTimes(ResetPassword::class, 1);
        Notification::assertSentOnDemand(
            ResetPassword::class,
            static fn(ResetPassword $mail, array $channels, AnonymousNotifiable $to): bool => $to->routes === ['mail' => 'student@example.com']
                && $mail->locale === 'en'
                && \strlen($mail->token) === 64,
        );

        // В базе — не сам токен из письма, а его SHA-256
        $stored = DB::table('password_reset_tokens')->where('email', 'student@example.com')->value('token');
        self::assertIsString($stored);
        self::assertNotSame($this->sentToken(), $stored);
        self::assertSame(hash('sha256', $this->sentToken()), $stored);
    }

    public function testUnknownEmailGetsTheSameAnswerAndNoMail(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertExactJson(self::SENT);

        Notification::assertNothingSent();
        self::assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function testMailLeadsToFrontendInStudentsLanguage(): void
    {
        Config::set('app.frontend_url', 'https://school.example/');
        UserFactory::new()->create(['email' => 'anya@example.com', 'locale' => 'ru']);
        UserFactory::new()->create(['email' => 'bob@example.com', 'locale' => 'en']);

        // Язык запроса не важен: письмо — на языке аккаунта
        $this->postJson('/api/auth/forgot-password', ['email' => 'anya@example.com'], ['Accept-Language' => 'en'])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'bob@example.com'])->assertOk();

        [$ru, $en] = $this->sentMails();

        self::assertSame('Новый пароль для C# School', $ru->getSubject());
        self::assertSame('anya@example.com', $ru->getTo()[0]->getAddress());
        $text = (string) $ru->getTextBody();
        self::assertStringContainsString('Придумать новый пароль', $text);
        self::assertStringContainsString('Ссылка действует 60 мин.', $text);
        self::assertMatchesRegularExpression('#https://school\.example/reset-password\?token=[A-Za-z0-9]{64}&email=anya%40example\.com#', $text);
        // Служебные строки шаблона Laravel тоже переведены (lang/ru.json)
        self::assertStringContainsString('скопируй ссылку ниже', (string) $ru->getHtmlBody());
        self::assertStringNotContainsString('trouble clicking', (string) $ru->getHtmlBody());

        self::assertSame('New password for C# School', $en->getSubject());
        self::assertStringContainsString('Set a new password', (string) $en->getTextBody());
    }

    public function testValidatesEmail(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'не почта'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'В поле «Почта» нужен адрес почты, например anya@example.com.');

        $this->postJson('/api/auth/forgot-password', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Заполни поле «Почта».');
    }

    public function testIsLimitedPerEmailAndPerAddress(): void
    {
        Notification::fake();

        // На одну почту — 3 письма за 10 минут, хоть аккаунт есть, хоть нет
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/auth/forgot-password', ['email' => 'anya@example.com'])->assertOk();
        }

        $this->postJson('/api/auth/forgot-password', ['email' => 'Anya@example.com'])->assertTooManyRequests();

        // С одного адреса — 5 запросов в минуту на любые почты (отклонённый тоже считается)
        $this->postJson('/api/auth/forgot-password', ['email' => 'bob@example.com'])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'kate@example.com'])->assertTooManyRequests();
    }

    private function sentToken(): string
    {
        $token = null;

        Notification::assertSentOnDemand(ResetPassword::class, static function (ResetPassword $mail) use (&$token): bool {
            $token = $mail->token;

            return true;
        });

        self::assertIsString($token);

        return $token;
    }

    /**
     * Письма, «отправленные» почтовым драйвером array (phpunit.xml)
     *
     * @return list<Email>
     */
    private function sentMails(): array
    {
        $transport = Mail::mailer()->getSymfonyTransport();
        self::assertInstanceOf(ArrayTransport::class, $transport);

        $mails = [];

        foreach ($transport->messages() as $sent) {
            $mail = $sent->getOriginalMessage();
            self::assertInstanceOf(Email::class, $mail);
            $mails[] = $mail;
        }

        return $mails;
    }
}
