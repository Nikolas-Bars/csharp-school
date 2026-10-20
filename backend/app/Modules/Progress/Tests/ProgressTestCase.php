<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tests;

use App\Modules\User\Database\Factories\UserFactory;
use App\Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Прогресс по тестовому курсу модуля Course (test-course: урок 01-first с задачей say-hi и
 * проверкой знаний, урок 02-second с одной проверкой знаний). Курс грузится через course:import.
 */
abstract class ProgressTestCase extends TestCase
{
    protected const string COURSE = '/api/progress/courses/test-course';

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
     * @param list<array<string, string|null>> $steps
     */
    protected function complete(array $steps): void
    {
        $this->postJson(self::COURSE . '/steps', ['steps' => $steps])->assertOk();
    }
}
