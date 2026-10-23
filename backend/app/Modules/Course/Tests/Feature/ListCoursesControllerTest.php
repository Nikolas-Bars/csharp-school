<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Tests\CourseTestCase;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;

final class ListCoursesControllerTest extends CourseTestCase
{
    public function testListsCoursesInCatalogOrderToGuests(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses')->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Курсы.',
            'data'    => [
                'items' => [
                    [
                        'slug'        => 'test-course',
                        'title'       => 'Тестовый курс',
                        'description' => 'Курс для тестов',
                        'modules'     => 1,
                        'lessons'     => 2,
                        'start'       => ['module' => '01-basics', 'lesson' => '01-first'],
                    ],
                    [
                        'slug'        => 'other-course',
                        'title'       => 'Второй курс',
                        'description' => 'Ещё один курс',
                        'modules'     => 1,
                        'lessons'     => 1,
                        'start'       => ['module' => '01-only', 'lesson' => '01-check'],
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

        $this->getJson('/api/courses', ['Accept-Language' => 'en'])
            ->assertJsonPath('data.items.0.title', 'Test course')
            ->assertJsonPath('data.items.0.description', 'Course for tests')
            ->assertJsonPath('data.items.1.title', 'Второй курс');
    }

    public function testEmptyBeforeImport(): void
    {
        $this->getJson('/api/courses')->assertOk()->assertJsonPath('data.items', [])->assertJsonPath('data.next_cursor', null);
    }

    public function testQueriesDoNotGrowWithCourses(): void
    {
        $this->importFixture();
        $queries = 0;
        $this->app?->make('db')->listen(static function () use (&$queries): void {
            ++$queries;
        });

        $this->getJson('/api/courses')->assertOk();

        // Курсы, модули, уроки — по запросу на таблицу, сколько бы курсов ни было
        self::assertSame(3, $queries);
    }

    public function testCursorOpensNextPage(): void
    {
        $this->importFixture();
        $first = Course::query()->where('slug', 'test-course')->sole();
        // Такой курсор Laravel выдаёт в next_cursor после первого курса
        $cursor = (new Cursor(['position' => $first->position, 'id' => $first->id], true))->encode();

        $this->getJson('/api/courses?cursor=' . $cursor)
            ->assertOk()
            ->assertJsonPath('data.items.0.slug', 'other-course')
            ->assertJsonCount(1, 'data.items');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forgedCursors(): iterable
    {
        yield 'не base64' => ['!!!'];
        yield 'пустой объект' => [base64_encode('{}')];
        yield 'не объект' => [base64_encode('1')];
        yield 'без колонок сортировки' => [base64_encode('{"_pointsToNextItems":true}')];
        yield 'чужая колонка' => [base64_encode('{"slug":"x","id":1,"_pointsToNextItems":true}')];
        yield 'значение — массив' => [base64_encode('{"position":[1],"id":1,"_pointsToNextItems":true}')];
        yield 'слишком длинный' => [str_repeat('a', 2000)];
    }

    #[DataProvider('forgedCursors')]
    public function testForgedCursorGivesFirstPage(string $cursor): void
    {
        $this->importFixture();

        $this->getJson('/api/courses?cursor=' . rawurlencode($cursor))
            ->assertOk()
            ->assertJsonPath('data.items.0.slug', 'test-course')
            ->assertJsonCount(2, 'data.items');
    }

    public function testCourseLeftOutOfCatalogIsHiddenButStillOpens(): void
    {
        $this->importFixture();
        $copy = sys_get_temp_dir() . '/courses-' . bin2hex(random_bytes(4));
        File::copyDirectory(self::FIXTURE, $copy);
        File::put("{$copy}/courses.json", '{"courses":["other-course"]}');

        try {
            $this->artisan('course:import', ['path' => $copy])->assertSuccessful();
        } finally {
            File::deleteDirectory($copy);
        }

        $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.slug', 'other-course');
        // Убранный курс не удалён: прогресс ссылается на его уроки, по ссылке он открывается
        $this->getJson('/api/courses/test-course')->assertOk();
    }
}
