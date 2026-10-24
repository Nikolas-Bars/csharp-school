<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tests\Feature;

use App\Modules\Vocabulary\Models\LearnedWord;
use App\Modules\Vocabulary\Tests\VocabularyTestCase;

final class ReviewWordsControllerTest extends VocabularyTestCase
{
    public function testGuestCannotReview(): void
    {
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertUnauthorized();
    }

    public function testRememberedMovesForwardForgottenComesBackTomorrow(): void
    {
        $this->freezeSecond();
        $this->actingAsStudent();
        $this->learn(['wear', 'at-the-moment']);

        $this->postJson(self::COURSE . '/reviews', ['reviews' => [
            ['word' => 'wear', 'remembered' => true],
            ['word' => 'at-the-moment', 'remembered' => false],
        ]])->assertOk()->assertJsonPath('message', 'Повторение сохранено.')->assertJsonPath('data.words', [
            ['word' => 'wear', 'box' => 1, 'due_at' => now()->addDays(3)->toIso8601String(), 'reviewed_at' => now()->toIso8601String()],
            ['word' => 'at-the-moment', 'box' => 0, 'due_at' => now()->addDay()->toIso8601String(), 'reviewed_at' => now()->toIso8601String()],
        ]);
    }

    public function testWordNotDueYetIsNotMovedAgain(): void
    {
        $this->actingAsStudent();
        $this->learn(['wear']);
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();

        // «Пройти ещё раз» или повтор запроса: слово ждёт через 3 дня — не сдвигается
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();
        self::assertSame(1, LearnedWord::query()->sole()->box);

        // Через три дня — пора
        $this->travel(3)->days();
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();
        self::assertSame(2, LearnedWord::query()->sole()->box);
    }

    public function testAnswerRightAfterAnotherIsNotCounted(): void
    {
        $this->actingAsStudent();
        $this->learn(['wear']);
        // «Не помню» на телефоне, сразу «Помню» в старой вкладке ноутбука — засчитан первый ответ
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => false]]])->assertOk();
        $this->travel(1)->minutes();
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();

        self::assertSame(0, LearnedWord::query()->sole()->box);
    }

    public function testDueTomorrowCountsAsTodayForStudentTimeZone(): void
    {
        $this->actingAsStudent();
        $this->learn(['wear']);
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => false]]])->assertOk();

        // «Завтра» у ученика восточнее наступает раньше: срок через сутки уже можно повторять
        $this->travel(1)->hours();
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();

        self::assertSame(1, LearnedWord::query()->sole()->box);
    }

    public function testFirstAnswerCountsAndUnknownWordsAreSkipped(): void
    {
        $this->actingAsStudent();
        $this->learn(['wear']);

        $this->postJson(self::COURSE . '/reviews', ['reviews' => [
            ['word' => 'wear', 'remembered' => false],
            ['word' => 'wear', 'remembered' => true],
            ['word' => 'at-the-moment', 'remembered' => true],
            ['word' => 'gone', 'remembered' => true],
        ]])->assertOk()->assertJsonCount(1, 'data.words');

        self::assertSame([['word_id' => LearnedWord::query()->sole()->word_id, 'box' => 0]], LearnedWord::query()->get(['word_id', 'box'])->toArray());
    }

    public function testInvalidReviewIsRejected(): void
    {
        $this->actingAsStudent();

        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reviews.0.remembered', 'errors');
        $this->postJson(self::COURSE . '/reviews', ['reviews' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reviews', 'errors');
    }
}
