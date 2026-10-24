<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Tests\CourseTestCase;

final class ShowCourseControllerTest extends CourseTestCase
{
    public function testReturnsCourseMapToGuests(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/test-course')->assertOk()->assertExactJson([
            'status'  => 'success',
            'message' => 'Курс.',
            'data'    => [
                'slug'    => 'test-course',
                'title'   => 'Тестовый курс',
                'modules' => [[
                    'slug'        => '01-basics',
                    'title'       => 'Основы',
                    'description' => 'Самое начало',
                    'lessons'     => [
                        ['slug' => '01-first', 'title' => 'Первый урок', 'kind' => 'lesson', 'tasks' => 1, 'words' => 0, 'steps' => ['say-hi', 'quiz']],
                        ['slug' => '02-second', 'title' => 'Второй урок', 'kind' => 'test', 'tasks' => 0, 'words' => 0, 'steps' => ['quiz']],
                    ],
                ]],
            ],
            'errors' => [],
        ]);
    }

    public function testEnglishWithRussianFallback(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/test-course', ['Accept-Language' => 'en'])
            ->assertJsonPath('data.title', 'Test course')
            ->assertJsonPath('data.modules.0.title', 'Основы');
    }

    public function testUnknownCourseIs404(): void
    {
        $this->getJson('/api/courses/nope')->assertNotFound()->assertJsonPath('message', 'Такого курса нет.');
    }
}
