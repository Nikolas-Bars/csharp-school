<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Tests\CourseTestCase;

final class ShowLessonControllerTest extends CourseTestCase
{
    private const string URL = '/api/courses/test-course/modules/01-basics/lessons/01-first';

    public function testReturnsLessonStepsWithoutSolutions(): void
    {
        $this->importFixture();

        $response = $this->getJson(self::URL)->assertOk();

        $response->assertJsonPath('data.title', 'Первый урок')
            ->assertJsonPath('data.module', ['slug' => '01-basics', 'title' => 'Основы'])
            ->assertJsonPath('data.course', ['slug' => 'test-course', 'title' => 'Тестовый курс'])
            ->assertJsonPath('data.steps.0', ['type' => 'theory', 'text' => "# Теория\n\nТекст.\n"])
            ->assertJsonPath('data.steps.1', [
                'type'      => 'task',
                'id'        => 'say-hi',
                'title'     => 'Скажи привет',
                'statement' => "Напечатай «Привет».\n",
                'starter'   => "using System;\n",
                'checks'    => [['kind' => 'output', 'expected' => 'Привет']],
                'messages'  => ['Нужно напечатать «Привет».'],
                'hints'     => ['Один', 'Два', 'Три'],
            ])
            ->assertJsonPath('data.steps.2.questions.0', [
                'question'    => 'Вопрос?',
                'options'     => ['Да', 'Нет'],
                'answer'      => 0,
                'explanation' => 'Потому что да.',
            ]);

        // Эталон и неверные решения не уходят ученику
        self::assertStringNotContainsString('Пока', (string) $response->getContent());
        self::assertStringNotContainsString('solution', (string) $response->getContent());
    }

    public function testTheoryInEnglish(): void
    {
        $this->importFixture();

        $this->getJson(self::URL, ['Accept-Language' => 'en'])->assertJsonPath('data.steps.0.text', "# Theory\n\nText.\n");
    }

    public function testLessonFromAnotherModuleIs404(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/test-course/modules/02-other/lessons/01-first')
            ->assertNotFound()
            ->assertJsonPath('message', 'Такого урока нет. Возможно, он переехал — открой карту курса.');
    }
}
