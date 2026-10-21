<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Course\Enums\LessonKind;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Tests\CourseTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;

final class ImportCourseCommandTest extends CourseTestCase
{
    private string $copy = '';

    protected function tearDown(): void
    {
        if ($this->copy !== '') {
            File::deleteDirectory($this->copy);
        }

        parent::tearDown();
    }

    public function testImportsCourseWithoutSolutions(): void
    {
        $this->artisan('course:import', ['path' => self::FIXTURE])
            ->expectsOutput('Курс test-course загружен: модулей — 1, уроков — 2.')
            ->assertSuccessful();

        $lesson = Lesson::query()->where('slug', '01-first')->sole();
        $task = $lesson->steps[1];

        self::assertSame(['ru' => "# Теория\n\nТекст.\n", 'en' => "# Theory\n\nText.\n"], $lesson->steps[0]['text']);
        self::assertSame(['type', 'id', 'title', 'statement', 'starter', 'checks', 'messages', 'hints'], array_keys($task));
        self::assertSame("using System;\n", $task['starter']);
    }

    public function testReimportKeepsIdsAndRemovesDeletedLessons(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $test = Lesson::query()->where('slug', '02-second')->sole()->id;

        // Урок убрали из модуля (осталась контрольная) и переименовали курс
        File::put("{$this->copy}/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"Самое начало"},"lessons":["02-second"]}');
        File::put("{$this->copy}/course.json", '{"slug":"test-course","title":{"ru":"Новое название"},"modules":["01-basics"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame([$test], Lesson::query()->pluck('id')->all());
        self::assertSame(['ru' => 'Новое название'], Course::query()->sole()->title);
    }

    public function testBrokenContentIsReportedAndNothingIsSaved(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/01-basics/02-second/lesson.json", '{"title": {"en": "No Russian"}, "steps": []}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('Ошибка в контенте курса — 01-basics/02-second/lesson.json: title')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function lessonsProgressCannotCount(): iterable
    {
        yield 'без проверки знаний' => ['{"title":{"ru":"x"},"steps":[]}'];
        yield 'две проверки знаний' => ['{"title":{"ru":"x"},"steps":[{"type":"quiz","questions":[]},{"type":"quiz","questions":[]}]}'];
        yield 'задача дважды' => ['{"title":{"ru":"x"},"steps":[{"type":"task","task":"say-hi"},{"type":"task","task":"say-hi"},{"type":"quiz","questions":[]}]}'];
        yield 'задача с id quiz' => ['{"title":{"ru":"x"},"steps":[{"type":"task","task":"quiz"},{"type":"quiz","questions":[]}]}'];
    }

    #[DataProvider('lessonsProgressCannotCount')]
    public function testLessonMustBeCountableForProgress(string $lesson): void
    {
        $this->copy = $this->copyFixture();
        File::copyDirectory("{$this->copy}/01-basics/01-first/tasks/say-hi", "{$this->copy}/01-basics/01-first/tasks/quiz");
        File::put("{$this->copy}/01-basics/01-first/lesson.json", $lesson);

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('01-basics/01-first/lesson.json')
            ->assertFailed();
    }

    public function testReimportRefreshesLessonSteps(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $directory = $this->app?->make(LessonDirectory::class);
        self::assertInstanceOf(LessonDirectory::class, $directory);
        self::assertSame(['say-hi', 'quiz'], $directory->lessonsOf('test-course')[0]->steps);

        // Задачу убрали из урока: Progress видит это сразу, а не после сброса кэша вручную
        File::put("{$this->copy}/01-basics/01-first/lesson.json", '{"title":{"ru":"x"},"steps":[{"type":"quiz","questions":[]}]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame(['quiz'], $directory->lessonsOf('test-course')[0]->steps);
    }

    public function testLessonKindComesFromContent(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        // Без «kind» — обычный урок, «test» — контрольная модуля
        self::assertSame(
            ['01-first' => LessonKind::Lesson, '02-second' => LessonKind::Test],
            Lesson::query()->orderBy('position')->get()->mapWithKeys(static fn(Lesson $lesson): array => [$lesson->slug => $lesson->kind])->all(),
        );

        // Контрольной стал другой урок — повторный импорт обновляет вид
        File::put("{$this->copy}/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"Самое начало"},"lessons":["02-second","01-first"]}');
        File::put("{$this->copy}/01-basics/02-second/lesson.json", '{"title":{"ru":"x"},"steps":[{"type":"quiz","questions":[]}]}');
        File::put("{$this->copy}/01-basics/01-first/lesson.json", '{"title":{"ru":"x"},"kind":"test","steps":[{"type":"quiz","questions":[]}]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame(
            ['02-second' => LessonKind::Lesson, '01-first' => LessonKind::Test],
            Lesson::query()->orderBy('position')->get()->mapWithKeys(static fn(Lesson $lesson): array => [$lesson->slug => $lesson->kind])->all(),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function modulesWithoutSingleLastTest(): iterable
    {
        $lesson = '{"title":{"ru":"x"},"steps":[{"type":"quiz","questions":[]}]}';
        $test = '{"title":{"ru":"x"},"kind":"test","steps":[{"type":"quiz","questions":[]}]}';

        yield 'без контрольной' => ['["01-first","02-second"]', $lesson, $lesson];
        yield 'две контрольные' => ['["01-first","02-second"]', $test, $test];
        yield 'контрольная не последняя' => ['["02-second","01-first"]', $lesson, $test];
    }

    #[DataProvider('modulesWithoutSingleLastTest')]
    public function testModuleEndsWithItsOnlyTest(string $order, string $first, string $second): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"x"},"lessons":' . $order . '}');
        File::put("{$this->copy}/01-basics/01-first/lesson.json", $first);
        File::put("{$this->copy}/01-basics/02-second/lesson.json", $second);

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('01-basics/module.json: контрольная')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testEmptyModuleOrCourseIsRejected(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"x"},"lessons":[]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('01-basics/module.json: lessons')
            ->assertFailed();

        File::put("{$this->copy}/course.json", '{"slug":"test-course","title":{"ru":"x"},"modules":[]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('course.json: modules')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownKinds(): iterable
    {
        yield 'неизвестный вид' => ['"exam"'];
        yield 'не строка' => ['true'];
        yield 'null' => ['null'];
    }

    #[DataProvider('unknownKinds')]
    public function testUnknownLessonKindIsRejected(string $kind): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/01-basics/02-second/lesson.json", '{"title":{"ru":"x"},"kind":' . $kind . ',"steps":[{"type":"quiz","questions":[]}]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('01-basics/02-second/lesson.json: kind')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testPathsOutsideContentAreRejected(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/01-basics/module.json", '{"title":{"ru":"x"},"description":{"ru":"x"},"lessons":["../../etc"]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('имена папок')
            ->assertFailed();
    }

    public function testRealCourseImports(): void
    {
        // Настоящий content/ репозитория: формат бэкенда и контента не разошлись
        $this->artisan('course:import')->assertSuccessful();

        self::assertTrue(Course::query()->where('slug', 'csharp')->exists());
    }

    private function copyFixture(): string
    {
        $copy = sys_get_temp_dir() . '/course-' . bin2hex(random_bytes(4));
        File::copyDirectory(self::FIXTURE, $copy);

        return $copy;
    }
}
