<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Tests\CourseTestCase;
use Illuminate\Support\Facades\File;

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
        $first = Lesson::query()->where('slug', '01-first')->sole()->id;

        // Урок убрали из модуля и переименовали курс
        File::put("{$this->copy}/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"Самое начало"},"lessons":["01-first"]}');
        File::put("{$this->copy}/course.json", '{"slug":"test-course","title":{"ru":"Новое название"},"modules":["01-basics"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame([$first], Lesson::query()->pluck('id')->all());
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
