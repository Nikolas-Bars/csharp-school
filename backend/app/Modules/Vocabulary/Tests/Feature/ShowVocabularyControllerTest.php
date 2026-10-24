<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Tests\Feature;

use App\Modules\Vocabulary\Tests\VocabularyTestCase;
use Illuminate\Support\Facades\File;

final class ShowVocabularyControllerTest extends VocabularyTestCase
{
    public function testGuestCannotSeeVocabulary(): void
    {
        $this->getJson(self::COURSE)->assertUnauthorized();
    }

    public function testEmptyAtFirst(): void
    {
        $this->actingAsStudent();

        $this->getJson(self::COURSE)->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Твой словарь.',
            'data'    => ['words' => []],
            'errors'  => [],
        ]);
    }

    public function testShowsOwnWordsOnly(): void
    {
        $this->freezeSecond();
        $this->actingAsStudent();
        $this->learn(['wear']);
        $this->actingAsStudent();
        $this->learn(['at-the-moment']);

        $this->getJson(self::COURSE)->assertOk()->assertJsonPath('data.words', [
            ['word' => 'at-the-moment', 'box' => 0, 'due_at' => now()->toIso8601String(), 'reviewed_at' => null],
        ]);
    }

    public function testWordRemovedFromCourseIsHidden(): void
    {
        $this->actingAsStudent();
        $this->learn(['wear', 'at-the-moment']);

        $copy = sys_get_temp_dir() . '/vocabulary-' . bin2hex(random_bytes(4));
        File::copyDirectory(base_path('app/Modules/Course/Tests/Fixtures/content'), $copy);
        File::put("{$copy}/other-course/01-only/01-check/words.json", '[{"id":"wear","word":"wear"}]');
        try {
            $this->artisan('course:import', ['path' => $copy])->assertSuccessful();
        } finally {
            File::deleteDirectory($copy);
        }

        $this->getJson(self::COURSE)->assertOk()->assertJsonCount(1, 'data.words')->assertJsonPath('data.words.0.word', 'wear');
    }

    public function testUnknownCourseIsEmpty(): void
    {
        $this->actingAsStudent();

        $this->getJson('/api/vocabulary/courses/nope')->assertOk()->assertJsonPath('data.words', []);
    }
}
