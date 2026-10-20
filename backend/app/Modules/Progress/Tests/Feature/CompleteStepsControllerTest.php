<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tests\Feature;

use App\Modules\Progress\Models\CompletedStep;
use App\Modules\Progress\Tests\ProgressTestCase;

final class CompleteStepsControllerTest extends ProgressTestCase
{
    public function testLessonIsDoneWhenTaskAndQuizArePassed(): void
    {
        $this->actingAsStudent();

        $this->postJson(self::COURSE . '/steps', ['steps' => [
            ['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'Console.Write("Привет");'],
        ]])->assertOk()->assertJsonPath('data.lessons.0', [
            'module'    => '01-basics',
            'lesson'    => '01-first',
            'steps'     => ['say-hi', 'quiz'],
            'completed' => ['say-hi'],
            'done'      => false,
        ]);

        $this->postJson(self::COURSE . '/steps', ['steps' => [['module' => '01-basics', 'lesson' => '01-first', 'step' => 'quiz']]])
            ->assertOk()
            ->assertJsonPath('message', 'Прогресс сохранён.')
            ->assertJsonPath('data.lessons.0.done', true)
            ->assertJsonPath('data.lessons.1.done', false);
    }

    public function testGuestProgressImportSkipsUnknownAndRepeats(): void
    {
        $this->actingAsStudent();

        // Прогресс гостя из старого браузера: урока и шага уже нет, один шаг дважды
        $this->complete([
            ['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'v1'],
            ['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'v2'],
            ['module' => '01-basics', 'lesson' => '99-removed', 'step' => 'quiz'],
            ['module' => '01-basics', 'lesson' => '02-second', 'step' => 'old-task'],
            ['module' => '01-basics', 'lesson' => '02-second', 'step' => 'quiz'],
        ]);

        self::assertSame(
            [['say-hi', 'v2'], ['quiz', null]],
            CompletedStep::query()->orderBy('id')->get()->map(static fn(CompletedStep $step): array => [$step->step, $step->code])->all(),
        );
    }

    public function testRepeatKeepsFirstCompletionAndUpdatesCode(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'first']]);
        $first = CompletedStep::query()->sole();

        $this->travel(1)->days();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'better']]);

        $again = CompletedStep::query()->sole();
        self::assertSame('better', $again->code);
        self::assertTrue($first->completed_at->equalTo($again->completed_at));
    }

    public function testStepWithoutCodeKeepsSavedCode(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'saved']]);
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => null]]);

        self::assertSame('saved', CompletedStep::query()->sole()->code);
    }

    public function testGuestProgressDoesNotReplaceSavedCode(): void
    {
        $this->actingAsStudent();
        // В аккаунте — свежее решение; у второго шага кода ещё нет
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'phone']]);

        $this->postJson(self::COURSE . '/steps', ['guest' => true, 'steps' => [
            ['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'old laptop'],
            ['module' => '01-basics', 'lesson' => '02-second', 'step' => 'quiz'],
        ]])->assertOk()->assertJsonPath('data.lessons.1.done', true);

        self::assertSame(['say-hi' => 'phone', 'quiz' => null], CompletedStep::query()->pluck('code', 'step')->all());
    }

    public function testGuestProgressFillsMissingCode(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => null]]);

        $this->postJson(self::COURSE . '/steps', ['guest' => true, 'steps' => [
            ['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => 'guest'],
        ]])->assertOk();

        self::assertSame('guest', CompletedStep::query()->sole()->code);
    }

    public function testUnknownCourseSavesNothing(): void
    {
        $this->actingAsStudent();

        $this->postJson('/api/progress/courses/nope/steps', ['steps' => [['module' => '01-basics', 'lesson' => '01-first', 'step' => 'quiz']]])
            ->assertOk()
            ->assertJsonPath('data.lessons', []);
        self::assertSame(0, CompletedStep::query()->count());
    }

    public function testTooManyRequests(): void
    {
        $this->actingAsStudent();
        $step = ['steps' => [['module' => '01-basics', 'lesson' => '01-first', 'step' => 'quiz']]];

        for ($i = 0; $i < 30; ++$i) {
            $this->postJson(self::COURSE . '/steps', $step)->assertOk();
        }

        $this->postJson(self::COURSE . '/steps', $step)->assertTooManyRequests();
    }

    public function testQuizHasNoCode(): void
    {
        $this->actingAsStudent();
        $this->complete([['module' => '01-basics', 'lesson' => '01-first', 'step' => 'quiz', 'code' => 'что-то']]);

        self::assertNull(CompletedStep::query()->sole()->code);
    }

    public function testValidation(): void
    {
        $this->actingAsStudent();

        $this->postJson(self::COURSE . '/steps', ['steps' => []])->assertUnprocessable();
        $this->postJson(self::COURSE . '/steps', ['steps' => [['module' => '../x', 'lesson' => '01-first', 'step' => 'quiz']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('steps.0.module');
        $this->postJson(self::COURSE . '/steps', ['steps' => [['module' => '01-basics', 'lesson' => '01-first', 'step' => 'say-hi', 'code' => str_repeat('a', 20_001)]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('steps.0.code');
    }

    public function testGuestCannotSaveToServer(): void
    {
        $this->postJson(self::COURSE . '/steps', ['steps' => [['module' => '01-basics', 'lesson' => '01-first', 'step' => 'quiz']]])
            ->assertUnauthorized();
    }
}
