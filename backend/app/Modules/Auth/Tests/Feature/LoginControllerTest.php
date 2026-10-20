<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\User\Database\Factories\UserFactory;
use Tests\TestCase;

final class LoginControllerTest extends TestCase
{
    public function testLogsInWithEmailInAnyCase(): void
    {
        UserFactory::new()->create(['nickname' => 'student', 'email' => 'student@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'Student@Example.com ', 'password' => UserFactory::PASSWORD])
            ->assertOk()
            ->assertJsonPath('message', 'Вход выполнен.')
            ->assertJsonPath('data.user.nickname', 'student')
            ->assertJson(['data' => ['token' => true]]);
    }

    public function testWrongPasswordAndUnknownEmailLookTheSame(): void
    {
        UserFactory::new()->create(['email' => 'student@example.com']);
        $expected = ['status' => 'error', 'message' => 'Неверная почта или пароль.', 'data' => null, 'errors' => []];

        $this->postJson('/api/auth/login', ['email' => 'student@example.com', 'password' => 'не тот'])
            ->assertUnauthorized()
            ->assertExactJson($expected);

        $this->postJson('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'не тот'])
            ->assertUnauthorized()
            ->assertExactJson($expected);
    }

    public function testPasswordGuessingIsLimitedPerEmail(): void
    {
        UserFactory::new()->create(['email' => 'student@example.com']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/auth/login', ['email' => 'student@example.com', 'password' => "guess{$attempt}"])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', ['email' => 'student@example.com', 'password' => UserFactory::PASSWORD])->assertTooManyRequests();
        // Другая почта с того же адреса — свой счётчик
        $this->postJson('/api/auth/login', ['email' => 'other@example.com', 'password' => 'x'])->assertUnauthorized();
    }
}
