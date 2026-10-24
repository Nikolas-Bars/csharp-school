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

    public function testReturnsPracticeAndExerciseQuiz(): void
    {
        $this->importFixture();

        $response = $this->getJson('/api/courses/other-course/modules/01-only/lessons/01-check')->assertOk();

        $response->assertJsonPath('data.steps.0.type', 'practice')
            ->assertJsonPath('data.steps.0.id', 'form')
            ->assertJsonPath('data.steps.0.exercises.0', [
                'kind'        => 'choice',
                'prompt'      => 'Как сказать «Я сейчас читаю»?',
                'explanation' => 'I → am, к глаголу -ing.',
                'options'     => [['text' => 'I read now.', 'why' => 'Это Present Simple.'], ['text' => 'I am reading now.', 'why' => null]],
                'answer'      => 1,
            ])
            ->assertJsonPath('data.steps.0.exercises.1', [
                'kind'        => 'gap',
                'prompt'      => 'Вставь глагол.',
                'explanation' => 'she → is.',
                'text'        => 'She ___ (cook) dinner now.',
                'accept'      => ['is cooking'],
                'mistakes'    => [['answer' => 'cooks', 'why' => 'now — нужен Continuous.']],
                'hint'        => 'am / is / are + -ing.',
                'exact'       => true,
            ])
            ->assertJsonPath('data.steps.1.type', 'quiz')
            ->assertJsonPath('data.steps.1.exercises.0.tiles', ['doing', 'you', 'What', 'are'])
            ->assertJsonPath('data.steps.1.exercises.0.exact', false)
            ->assertJsonPath('data.steps.1.exercises.1.text', 'Я работаю.')
            ->assertJsonPath('data.steps.1.exercises.2.text', 'She are waiting.')
            ->assertJsonPath('data.steps.1.exercises.2.wrong', 1);
        self::assertArrayNotHasKey('questions', (array) $response->json('data.steps.1'));
    }

    public function testReturnsWordCards(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/other-course/modules/01-only/lessons/01-check')
            ->assertOk()
            ->assertJsonPath('data.steps.2.type', 'words')
            ->assertJsonPath('data.steps.2.words.0', [
                'id'          => 'wear',
                'word'        => 'wear',
                'ipa'         => '/wɛr/',
                'pos'         => 'verb',
                'translation' => 'носить (одежду)',
                'forms'       => ['wear', 'wore', 'worn'],
                'examples'    => [['en' => 'She is wearing a red jacket.', 'translation' => 'На ней красная куртка.', 'why' => 'Сейчас — Present Continuous.']],
                'compare'     => '**Не путай с put on.**',
            ])
            ->assertJsonPath('data.steps.2.words.1.forms', null)
            ->assertJsonPath('data.steps.2.words.1.compare', null);
    }

    public function testExercisesInEnglishWithRussianFallback(): void
    {
        $this->importFixture();

        $this->getJson('/api/courses/other-course/modules/01-only/lessons/01-check', ['Accept-Language' => 'en'])
            ->assertJsonPath('data.steps.0.exercises.0.prompt', 'How do you say “I am reading now”?')
            ->assertJsonPath('data.steps.0.exercises.0.explanation', 'I → am, к глаголу -ing.')
            ->assertJsonPath('data.steps.1.exercises.1.text', 'I am working (translate).');
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
