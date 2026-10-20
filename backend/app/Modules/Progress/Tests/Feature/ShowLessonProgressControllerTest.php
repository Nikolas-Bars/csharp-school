<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tests\Feature;

use App\Modules\Progress\Tests\ProgressTestCase;
use App\Modules\User\Database\Factories\UserFactory;
use Laravel\Sanctum\Sanctum;

final class ShowLessonProgressControllerTest extends ProgressTestCase
{
    private const string LESSON = self::COURSE . '/modules/01-basics/lessons/01-first';

    public function testCompletedStepsAndLastSolution(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'Console.Write("Привет");']]);

        $this->getJson(self::LESSON)->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Прогресс урока.',
            'data'    => ['completed' => ['say-hi'], 'solutions' => ['say-hi' => 'Console.Write("Привет");']],
            'errors'  => [],
        ]);
    }

    public function testNothingYetIsAnEmptyObject(): void
    {
        $this->actingAsStudent();

        self::assertStringContainsString('"solutions":{}', (string) $this->getJson(self::LESSON)->assertOk()->getContent());
    }

    public function testOtherStudentsSolutionsAreNotShown(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'чужой код']]);

        Sanctum::actingAs(UserFactory::new()->create());
        $response = $this->getJson(self::LESSON)->assertOk()->assertJsonPath('data.completed', []);

        self::assertStringNotContainsString('чужой код', (string) $response->getContent());
    }

    public function testGuestGets401(): void
    {
        $this->getJson(self::LESSON)->assertUnauthorized();
    }

    public function testUnknownLessonIs404(): void
    {
        $this->actingAsStudent();

        $this->getJson(self::COURSE . '/modules/01-basics/lessons/nope')->assertNotFound()->assertJsonPath('message', 'Такого урока нет.');
    }
}
