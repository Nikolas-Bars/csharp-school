<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\User\Database\Factories\UserFactory;
use App\Modules\User\Models\User;
use Tests\TestCase;

final class RegisterControllerTest extends TestCase
{
    public function testRegistersAndLogsIn(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'nickname' => '  hero_anya ',
            'email'    => ' Anya@Example.COM ',
            'password' => 'секретный пароль',
        ], ['Accept-Language' => 'en']);

        $response->assertCreated()
            ->assertJsonPath('message', 'Account created. Welcome!')
            ->assertJsonPath('data.user.nickname', 'hero_anya')
            ->assertJsonPath('data.user.email', 'anya@example.com')
            ->assertJsonPath('data.user.locale', 'en')
            ->assertJsonMissingPath('data.user.password');

        // С выданным токеном сразу открывается профиль
        $token = $response->json('data.token');
        self::assertIsString($token);
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$token}"])->assertOk()->assertJsonPath('data.nickname', 'hero_anya');

        $user = User::query()->where('email', 'anya@example.com')->sole();
        self::assertNotSame('секретный пароль', $user->password);
    }

    public function testEmailTakenIsAFieldError(): void
    {
        UserFactory::new()->create(['email' => 'anya@example.com']);

        $this->postJson('/api/auth/register', ['nickname' => 'anya', 'email' => 'ANYA@example.com', 'password' => 'password1'])
            ->assertUnprocessable()
            ->assertExactJson([
                'status'  => 'error',
                'message' => 'Проверь, правильно ли заполнены поля.',
                'data'    => null,
                'errors'  => ['email' => ['Эта почта уже занята. Если это твой аккаунт — войди в него.']],
            ]);
    }

    public function testValidatesFieldsInRussian(): void
    {
        $this->postJson('/api/auth/register', ['nickname' => 'a', 'email' => 'не почта', 'password' => '123'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nickname.0', 'В поле «Ник» не меньше 2 символов.')
            ->assertJsonPath('errors.email.0', 'В поле «Почта» нужен адрес почты, например anya@example.com.')
            ->assertJsonPath('errors.password.0', 'В поле «Пароль» не меньше 8 символов.');
    }

    public function testRegistrationIsRateLimited(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/auth/register', [])->assertUnprocessable();
        }

        $this->postJson('/api/auth/register', [])->assertTooManyRequests();
    }
}
