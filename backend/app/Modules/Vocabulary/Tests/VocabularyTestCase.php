<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tests;

use App\Modules\User\Database\Factories\UserFactory;
use App\Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Словарь по тестовому курсу модуля Course: в other-course две карточки — wear и at-the-moment.
 * Курс грузится через course:import.
 */
abstract class VocabularyTestCase extends TestCase
{
    protected const string COURSE = '/api/vocabulary/courses/other-course';

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('course:import', ['path' => base_path('app/Modules/Course/Tests/Fixtures/content')])->assertSuccessful();
    }

    protected function actingAsStudent(): User
    {
        $user = UserFactory::new()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param list<string> $words
     */
    protected function learn(array $words): void
    {
        $this->postJson(self::COURSE . '/words', ['words' => array_map(static fn(string $word): array => ['word' => $word], $words)])->assertOk();
    }
}
