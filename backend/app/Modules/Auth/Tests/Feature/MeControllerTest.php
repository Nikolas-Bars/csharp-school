<?php

declare(strict_types=1);

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\User\Database\Factories\UserFactory;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MeControllerTest extends TestCase
{
    public function testReturnsCurrentUserWithoutPassword(): void
    {
        Sanctum::actingAs(UserFactory::new()->create(['nickname' => 'student', 'email' => 'student@example.com', 'locale' => 'en']));

        $this->getJson('/api/auth/me')->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Твой аккаунт.',
            'data'    => ['id' => 1, 'nickname' => 'student', 'email' => 'student@example.com', 'locale' => 'en'],
            'errors'  => [],
        ]);
    }

    public function testGuestGets401(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function testBrokenTokenGets401(): void
    {
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer 1|неправильный'])->assertUnauthorized();
    }
}
