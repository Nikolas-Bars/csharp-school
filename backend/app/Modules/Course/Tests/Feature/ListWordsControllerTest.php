<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Models\Word;
use App\Modules\Course\Tests\CourseTestCase;
use Illuminate\Pagination\Cursor;

final class ListWordsControllerTest extends CourseTestCase
{
    public function testReturnsCourseWordsToGuests(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/other-course/words')->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Слова курса.',
            'data'    => [
                'items' => [
                    [
                        'id'          => 'wear',
                        'word'        => 'wear',
                        'ipa'         => '/wɛr/',
                        'pos'         => 'verb',
                        'translation' => 'носить (одежду)',
                        'forms'       => ['wear', 'wore', 'worn'],
                        'examples'    => [['en' => 'She is wearing a red jacket.', 'translation' => 'На ней красная куртка.', 'why' => 'Сейчас — Present Continuous.']],
                        'compare'     => '**Не путай с put on.**',
                        'module'      => ['slug' => '01-only', 'title' => 'Единственный модуль'],
                        'lesson'      => ['slug' => '01-check', 'title' => 'Контрольная'],
                    ],
                    [
                        'id'          => 'at-the-moment',
                        'word'        => 'at the moment',
                        'ipa'         => '/æt ðə ˈmoʊmənt/',
                        'pos'         => 'phrase',
                        'translation' => 'сейчас',
                        'forms'       => null,
                        'examples'    => [['en' => 'I am busy at the moment.', 'translation' => 'Я сейчас занят.', 'why' => 'Маркер Continuous.']],
                        'compare'     => null,
                        'module'      => ['slug' => '01-only', 'title' => 'Единственный модуль'],
                        'lesson'      => ['slug' => '01-check', 'title' => 'Контрольная'],
                    ],
                ],
                'next_cursor' => null,
            ],
            'errors' => [],
        ]);
    }

    public function testEnglishWithRussianFallback(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/other-course/words', ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.items.0.translation', 'to have clothes on')
            ->assertJsonPath('data.items.0.examples.0.why', 'Сейчас — Present Continuous.')
            ->assertJsonPath('data.items.1.translation', 'сейчас');
    }

    public function testCourseWithoutWordsIsEmpty(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/test-course/words')->assertOk()->assertJsonPath('data.items', []);
    }

    public function testCursorOpensNextPage(): void
    {
        $this->importFixture();
        $first = Word::query()->where('slug', 'wear')->sole();
        $cursor = (new Cursor(['position' => $first->position, 'id' => $first->id], true))->encode();

        $this->getJson('/api/courses/other-course/words?cursor=' . $cursor)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', 'at-the-moment');
    }

    public function testUnknownCourseIs404(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/nope/words')->assertNotFound();
    }
}
