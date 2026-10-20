<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\User\Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ResetPasswordControllerTest extends TestCase
{
    private const string NEW_PASSWORD = 'новый пароль 2';

    private const array INVALID_LINK = [
        'status'  => 'error',
        'message' => 'Ссылка для нового пароля не подходит: она устарела или уже использована. Запроси новую.',
        'data'    => null,
        'errors'  => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        UserFactory::new()->create(['email' => 'student@example.com']);
    }

    public function testSetsNewPasswordAndLogsOutEverywhere(): void
    {
        $phone = $this->login(UserFactory::PASSWORD);
        $laptop = $this->login(UserFactory::PASSWORD);
        $token = $this->requestLink();

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'email'    => ' Student@Example.com',
            'password' => self::NEW_PASSWORD,
        ])->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Пароль изменён. Теперь войди с новым паролем.',
            'data'    => null,
            'errors'  => [],
        ]);

        // Входы на всех устройствах закрыты
        foreach ([$phone, $laptop] as $session) {
            Auth::forgetGuards();
            $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$session}"])->assertUnauthorized();
        }

        self::assertSame(0, DB::table('personal_access_tokens')->count());
        self::assertSame(0, DB::table('password_reset_tokens')->count());

        // Старый пароль больше не подходит, новый — подходит
        $this->postJson('/api/auth/login', ['email' => 'student@example.com', 'password' => UserFactory::PASSWORD])->assertUnauthorized();
        $this->login(self::NEW_PASSWORD);
    }

    public function testLinkWorksOnlyOnce(): void
    {
        $token = $this->requestLink();
        $reset = ['token' => $token, 'email' => 'student@example.com', 'password' => self::NEW_PASSWORD];

        $this->postJson('/api/auth/reset-password', $reset)->assertOk();
        $this->postJson('/api/auth/reset-password', [...$reset, 'password' => 'ещё один пароль'])
            ->assertBadRequest()
            ->assertExactJson(self::INVALID_LINK);
    }

    public function testOnlyTheNewestLinkWorks(): void
    {
        $old = $this->requestLink();
        $new = $this->requestLink();

        $this->reset($old)->assertBadRequest();
        $this->reset($new)->assertOk();
    }

    public function testWrongTokenAndUnknownEmailLookTheSame(): void
    {
        $this->requestLink();

        $this->reset(str_repeat('a', 64))->assertBadRequest()->assertExactJson(self::INVALID_LINK);
        $this->reset(str_repeat('a', 64), 'nobody@example.com')->assertBadRequest()->assertExactJson(self::INVALID_LINK);

        // Пароль не изменился
        $this->login(UserFactory::PASSWORD);
    }

    public function testExpiredLinkDoesNotWork(): void
    {
        $token = $this->requestLink();

        // Срок ссылки — auth.passwords.users.expire, 60 минут
        $this->travel(61)->minutes();

        $this->reset($token)->assertBadRequest()->assertExactJson(self::INVALID_LINK);
    }

    public function testValidatesPasswordLikeRegistration(): void
    {
        $token = $this->requestLink();

        $this->reset($token, password: '1234567')
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'В поле «Новый пароль» не меньше 8 символов.');

        $this->reset($token, password: str_repeat('x', 73))
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'В поле «Новый пароль» не больше 72 символов.');

        $this->postJson('/api/auth/reset-password', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', 'Заполни поле «Ссылка из письма».')
            ->assertJsonPath('errors.email.0', 'Заполни поле «Почта».')
            ->assertJsonPath('errors.password.0', 'Заполни поле «Новый пароль».');

        // Ошибка в форме ссылку не тратит
        $this->reset($token)->assertOk();
    }

    public function testGuessingTokensIsLimited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->reset("guess{$attempt}")->assertBadRequest();
        }

        $this->reset('guess6')->assertTooManyRequests();
        // Другая почта с того же адреса — свой счётчик, но общий лимит адреса — 10 в минуту
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->reset("guess{$attempt}", "other{$attempt}@example.com")->assertBadRequest();
        }

        $this->reset('guess', 'last@example.com')->assertTooManyRequests();
    }

    private function requestLink(): string
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'student@example.com'])->assertOk();

        $token = null;

        Notification::assertSentOnDemand(ResetPassword::class, static function (ResetPassword $mail) use (&$token): bool {
            $token = $mail->token;

            return true;
        });

        self::assertIsString($token);

        return $token;
    }

    private function reset(string $token, string $email = 'student@example.com', string $password = self::NEW_PASSWORD): TestResponse
    {
        return $this->postJson('/api/auth/reset-password', ['token' => $token, 'email' => $email, 'password' => $password]);
    }

    private function login(string $password): string
    {
        $token = $this->postJson('/api/auth/login', ['email' => 'student@example.com', 'password' => $password])->assertOk()->json('data.token');
        self::assertIsString($token);

        return $token;
    }
}
