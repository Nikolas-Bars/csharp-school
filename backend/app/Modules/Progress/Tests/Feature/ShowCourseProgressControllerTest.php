<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tests\Feature;

use App\Modules\Progress\Tests\ProgressTestCase;
use App\Modules\User\Database\Factories\UserFactory;
use Laravel\Sanctum\Sanctum;

final class ShowCourseProgressControllerTest extends ProgressTestCase
{
    public function testShowsOnlyOwnProgressWithoutCode(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '02-second', 'step' => 'quiz']]);

        Sanctum::actingAs(UserFactory::new()->create());
        $this->getJson(self::COURSE)->assertOk()->assertJsonPath('data.lessons.1.completed', []);
    }

    public function testCourseMapProgress(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'секрет']]);

        $response = $this->getJson(self::COURSE)->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Прогресс по курсу.',
            'data'    => ['lessons' => [
                ['module' => '01-basics', 'lesson' => '01-first', 'steps' => ['say-hi', 'quiz'], 'completed' => ['say-hi'], 'done' => false],
                ['module' => '01-basics', 'lesson' => '02-second', 'steps' => ['quiz'], 'completed' => [], 'done' => false],
            ]],
            'errors' => [],
        ]);

        self::assertStringNotContainsString('секрет', (string) $response->getContent());
    }

    public function testUnknownCourseHasNoLessons(): void
    {
        $this->actingAsStudent();

        $this->getJson('/api/progress/courses/nope')->assertOk()->assertJsonPath('data.lessons', []);
    }

    public function testGuestGets401(): void
    {
        $this->getJson(self::COURSE)->assertUnauthorized();
    }
}
