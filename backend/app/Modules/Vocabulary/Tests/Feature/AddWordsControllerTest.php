<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tests\Feature;

use App\Modules\Vocabulary\Models\LearnedWord;
use App\Modules\Vocabulary\Tests\VocabularyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AddWordsControllerTest extends VocabularyTestCase
{
    public function testGuestCannotAddWords(): void
    {
        $this->postJson(self::COURSE . '/words', ['words' => [['word' => 'wear']]])->assertUnauthorized();
    }

    public function testNewWordsWaitForReviewNow(): void
    {
        $this->freezeSecond();
        $this->actingAsStudent();

        $this->postJson(self::COURSE . '/words', ['words' => [['word' => 'wear'], ['word' => 'gone'], ['word' => 'wear']]])
            ->assertOk()
            ->assertJsonPath('message', 'Слова добавлены в словарь.')
            ->assertJsonPath('data.words', [['word' => 'wear', 'box' => 0, 'due_at' => now()->toIso8601String(), 'reviewed_at' => null]]);
    }

    public function testLearnedWordKeepsItsSchedule(): void
    {
        $this->actingAsStudent();
        $this->learn(['wear']);
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();

        // Урок прошли снова: слово уже выучено, расписание не сбрасывается
        $this->learn(['wear']);

        self::assertSame(1, LearnedWord::query()->sole()->box);
    }

    public function testScheduleIsTakenOnlyFromGuest(): void
    {
        $this->actingAsStudent();

        $this->postJson(self::COURSE . '/words', ['words' => [['word' => 'wear', 'box' => 3, 'due_at' => now()->addDays(5)->toIso8601String()]]])
            ->assertOk()
            ->assertJsonPath('data.words.0.box', 0);
    }

    public function testGuestVocabularyOnlyFillsAccount(): void
    {
        $this->freezeSecond();
        $this->actingAsStudent();
        // В аккаунте wear уже повторено; гость в старом браузере знает оба слова
        $this->learn(['wear']);
        $this->postJson(self::COURSE . '/reviews', ['reviews' => [['word' => 'wear', 'remembered' => true]]])->assertOk();
        $due = now()->addDays(7);

        $this->postJson(self::COURSE . '/words', ['guest' => true, 'words' => [
            ['word' => 'wear', 'box' => 4, 'due_at' => now()->addDays(30)->toIso8601String()],
            ['word' => 'at-the-moment', 'box' => 2, 'due_at' => $due->toIso8601String()],
        ]])->assertOk()->assertJsonPath('data.words', [
            ['word' => 'wear', 'box' => 1, 'due_at' => now()->addDays(3)->toIso8601String(), 'reviewed_at' => now()->toIso8601String()],
            ['word' => 'at-the-moment', 'box' => 2, 'due_at' => $due->toIso8601String(), 'reviewed_at' => null],
        ]);
    }

    public function testGuestScheduleIsStoredInUtcAndClampedToItsBox(): void
    {
        $this->freezeSecond();
        $this->actingAsStudent();

        // Срок со смещением — в UTC; часы гостя убежали на год вперёд — срок не дальше, чем даёт коробка
        $this->postJson(self::COURSE . '/words', ['guest' => true, 'words' => [
            ['word' => 'wear', 'box' => 2, 'due_at' => now()->addDays(2)->setTimezone('+03:00')->toIso8601String()],
            ['word' => 'at-the-moment', 'box' => 1, 'due_at' => now()->addYear()->toIso8601String()],
        ]])->assertOk()->assertJsonPath('data.words', [
            ['word' => 'wear', 'box' => 2, 'due_at' => now()->addDays(2)->toIso8601String(), 'reviewed_at' => null],
            ['word' => 'at-the-moment', 'box' => 1, 'due_at' => now()->addDays(3)->toIso8601String(), 'reviewed_at' => null],
        ]);
    }

    public function testUnknownCourseSavesNothing(): void
    {
        $this->actingAsStudent();

        $this->postJson('/api/vocabulary/courses/nope/words', ['words' => [['word' => 'wear']]])->assertOk()->assertJsonPath('data.words', []);
        self::assertSame(0, LearnedWord::query()->count());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidRequests(): iterable
    {
        yield 'без слов' => [['words' => []], 'words'];
        yield 'слово не slug' => [['words' => [['word' => '../x']]], 'words.0.word'];
        yield 'лишнее поле' => [['words' => [['word' => 'wear', 'lesson' => 'x']]], 'words.0'];
        yield 'коробка больше последней' => [['guest' => true, 'words' => [['word' => 'wear', 'box' => 5]]], 'words.0.box'];
        yield 'коробка строкой' => [['guest' => true, 'words' => [['word' => 'wear', 'box' => '2']]], 'words.0.box'];
        yield 'коробка дробью' => [['guest' => true, 'words' => [['word' => 'wear', 'box' => 2.5]]], 'words.0.box'];
        yield 'срок не дата' => [['guest' => true, 'words' => [['word' => 'wear', 'box' => 1, 'due_at' => 'завтра']]], 'words.0.due_at'];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('invalidRequests')]
    public function testInvalidRequestIsRejected(array $body, string $field): void
    {
        $this->actingAsStudent();

        $this->postJson(self::COURSE . '/words', $body)->assertUnprocessable()->assertJsonValidationErrors($field, 'errors');
    }

    public function testTooManyRequests(): void
    {
        $this->actingAsStudent();

        for ($i = 0; $i < 30; ++$i) {
            $this->learn(['wear']);
        }

        $this->postJson(self::COURSE . '/words', ['words' => [['word' => 'wear']]])->assertTooManyRequests();
    }
}
