<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\User\Database\Factories\UserFactory;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class LogoutControllerTest extends TestCase
{
    public function testRevokesOnlyCurrentDeviceToken(): void
    {
        UserFactory::new()->create(['email' => 'student@example.com']);
        $phone = $this->login();
        $laptop = $this->login();

        $this->postJson('/api/auth/logout', [], ['Authorization' => "Bearer {$phone}"])
            ->assertOk()
            ->assertJsonPath('message', 'Ты вышел из аккаунта.');

        // Guard запоминает пользователя между запросами одного теста — сбрасываем, как будто новый запрос
        Auth::forgetGuards();
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$phone}"])->assertUnauthorized();
        Auth::forgetGuards();
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$laptop}"])->assertOk();
    }

    public function testGuestCannotLogOut(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized()->assertJsonPath('message', 'Сначала войди в аккаунт.');
    }

    private function login(): string
    {
        $token = $this->postJson('/api/auth/login', ['email' => 'student@example.com', 'password' => UserFactory::PASSWORD])->json('data.token');
        self::assertIsString($token);

        return $token;
    }
}
