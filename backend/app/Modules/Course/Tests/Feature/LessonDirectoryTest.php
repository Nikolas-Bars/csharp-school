<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Course\Tests\CourseTestCase;

/** Контракт для Progress: уроки курса и их засчитываемые шаги — без лишних запросов на урок */
final class LessonDirectoryTest extends CourseTestCase
{
    public function testLessonsWithTaskAndQuizSteps(): void
    {
        $this->importFixture();
        $directory = $this->app?->make(LessonDirectory::class);
        self::assertInstanceOf(LessonDirectory::class, $directory);

        $lessons = $directory->lessonsOf('test-course');

        self::assertSame(
            [['01-basics/01-first', ['say-hi', 'quiz']], ['01-basics/02-second', ['quiz']]],
            array_map(static fn(LessonStepsDTO $lesson): array => [$lesson->path(), $lesson->steps], $lessons),
        );
        self::assertSame([], $directory->lessonsOf('nope'));
    }
}
