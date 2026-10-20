<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests;

use App\Modules\Course\Actions\ImportCourseAction;
use Tests\TestCase;

/** Тесты модуля на небольшом тестовом курсе (Tests/Fixtures/content), а не на настоящем */
abstract class CourseTestCase extends TestCase
{
    protected const string FIXTURE = __DIR__ . '/Fixtures/content';

    protected function importFixture(): void
    {
        $this->app?->make(ImportCourseAction::class)->run(self::FIXTURE);
    }
}
