<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Feature;

use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Course\Contracts\WordDirectory;
use App\Modules\Course\Enums\LessonKind;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Models\Word;
use App\Modules\Course\Tests\CourseTestCase;
use Illuminate\Database\Eloquent\Builder;
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
            ->expectsOutput('Курс other-course загружен: модулей — 1, уроков — 1.')
            ->assertSuccessful();

        $lesson = Lesson::query()->where('slug', '01-first')->sole();
        $task = $lesson->steps[1];

        self::assertSame(['ru' => "# Теория\n\nТекст.\n", 'en' => "# Theory\n\nText.\n"], $lesson->steps[0]['text']);
        self::assertSame(['type', 'id', 'title', 'statement', 'starter', 'checks', 'messages', 'hints'], array_keys($task));
        self::assertSame("using System;\n", $task['starter']);
    }

    public function testRealContentImports(): void
    {
        // Настоящий content/ (config course.content_path): все курсы courses.json проходят импорт
        $this->artisan('course:import')->assertSuccessful();

        self::assertContains('english', Course::query()->pluck('slug')->all());
        self::assertContains('csharp', Course::query()->pluck('slug')->all());
        // У каждого урока английского засчитываются практики и проверка знаний
        $english = Lesson::query()->whereHas('module.course', static fn(Builder $query) => $query->where('slug', 'english'))->get();
        self::assertNotEmpty($english);
        foreach ($english as $lesson) {
            self::assertContains('quiz', $lesson->countedSteps(), $lesson->slug);
            self::assertGreaterThan(1, \count($lesson->countedSteps()), $lesson->slug);
        }
    }

    public function testReimportKeepsIdsAndRemovesDeletedLessons(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $test = Lesson::query()->where('slug', '02-second')->sole()->id;

        // Урок убрали из модуля (осталась контрольная) и переименовали курс
        File::put("{$this->copy}/test-course/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"Самое начало"},"lessons":["02-second"]}');
        File::put("{$this->copy}/test-course/course.json", '{"slug":"test-course","title":{"ru":"Новое название"},"description":{"ru":"x"},"modules":["01-basics"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame([$test], Lesson::query()->whereHas('module.course', static fn(Builder $query) => $query->where('slug', 'test-course'))->pluck('id')->all());
        self::assertSame(['ru' => 'Новое название'], Course::query()->where('slug', 'test-course')->sole()->title);
    }

    public function testBrokenContentIsReportedAndNothingIsSaved(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/test-course/01-basics/02-second/lesson.json", '{"title": {"en": "No Russian"}, "steps": []}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('Ошибка в контенте курса — test-course/01-basics/02-second/lesson.json: title')
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
        File::copyDirectory("{$this->copy}/test-course/01-basics/01-first/tasks/say-hi", "{$this->copy}/test-course/01-basics/01-first/tasks/quiz");
        File::put("{$this->copy}/test-course/01-basics/01-first/lesson.json", $lesson);

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('test-course/01-basics/01-first/lesson.json')
            ->assertFailed();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function brokenPractices(): iterable
    {
        $quiz = '{"type":"quiz","questions":[]}';

        yield 'id практики как у задачи' => ['[{"type":"task","task":"say-hi"},{"type":"practice","id":"say-hi","file":"practice.json"},' . $quiz . ']', 'lesson.json: id задач и практик'];
        yield 'id практики quiz' => ['[{"type":"practice","id":"quiz","file":"practice.json"},' . $quiz . ']', 'lesson.json: id задач и практик'];
        yield 'две практики с одним id' => ['[{"type":"practice","id":"form","file":"practice.json"},{"type":"practice","id":"form","file":"practice.json"},' . $quiz . ']', 'lesson.json: id задач и практик'];
        yield 'файл вне урока' => ['[{"type":"practice","id":"form","file":"../02-second/lesson.json"},' . $quiz . ']', 'file — JSON рядом с уроком'];
        yield 'не JSON' => ['[{"type":"practice","id":"form","file":"theory.ru.md"},' . $quiz . ']', 'file — JSON рядом с уроком'];
        yield 'нет файла' => ['[{"type":"practice","id":"form","file":"missing.json"},' . $quiz . ']', '01-first/missing.json: файла нет'];
        yield 'неизвестный вид упражнения' => ['[{"type":"practice","id":"form","file":"unknown.json"},' . $quiz . ']', '01-first/unknown.json: [1]: kind'];
        yield 'пустой список' => ['[{"type":"practice","id":"form","file":"empty.json"},' . $quiz . ']', '01-first/empty.json: список упражнений'];
        yield 'квиз из пустого файла' => ['[{"type":"quiz","file":"empty.json"}]', '01-first/empty.json: список упражнений'];
    }

    #[DataProvider('brokenPractices')]
    public function testBrokenPracticeIsRejected(string $steps, string $error): void
    {
        $this->copy = $this->copyFixture();
        $lesson = "{$this->copy}/test-course/01-basics/01-first";
        File::put("{$lesson}/practice.json", '[{"kind":"choice"}]');
        File::put("{$lesson}/unknown.json", '[{"kind":"gap"},{"kind":"essay"}]');
        File::put("{$lesson}/empty.json", '[]');
        File::put("{$lesson}/lesson.json", '{"title":{"ru":"x"},"steps":' . $steps . '}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain($error)
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testPracticeAndQuizExercisesComeFromFiles(): void
    {
        $this->importFixture();

        $steps = Lesson::query()->where('slug', '01-check')->sole()->steps;

        self::assertSame(['practice', 'form'], [$steps[0]['type'], $steps[0]['id']]);
        self::assertSame(['choice', 'gap'], array_column(\is_array($steps[0]['exercises']) ? $steps[0]['exercises'] : [], 'kind'));
        self::assertSame(['order', 'translate', 'fix'], array_column(\is_array($steps[1]['exercises']) ? $steps[1]['exercises'] : [], 'kind'));
        self::assertSame(['form', 'quiz'], Lesson::query()->where('slug', '01-check')->sole()->countedSteps());
    }

    public function testReimportRefreshesLessonSteps(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $directory = $this->app?->make(LessonDirectory::class);
        self::assertInstanceOf(LessonDirectory::class, $directory);
        self::assertSame(['say-hi', 'quiz'], $directory->lessonsOf('test-course')[0]->steps);

        // Задачу убрали из урока: Progress видит это сразу, а не после сброса кэша вручную
        File::put("{$this->copy}/test-course/01-basics/01-first/lesson.json", '{"title":{"ru":"x"},"steps":[{"type":"quiz","questions":[]}]}');
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
            Lesson::query()->where('slug', '!=', '01-check')->orderBy('position')->get()->mapWithKeys(static fn(Lesson $lesson): array => [$lesson->slug => $lesson->kind])->all(),
        );

        // Контрольной стал другой урок — повторный импорт обновляет вид
        File::put("{$this->copy}/test-course/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"Самое начало"},"lessons":["02-second","01-first"]}');
        File::put("{$this->copy}/test-course/01-basics/02-second/lesson.json", '{"title":{"ru":"x"},"steps":[{"type":"quiz","questions":[]}]}');
        File::put("{$this->copy}/test-course/01-basics/01-first/lesson.json", '{"title":{"ru":"x"},"kind":"test","steps":[{"type":"quiz","questions":[]}]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame(
            ['02-second' => LessonKind::Lesson, '01-first' => LessonKind::Test],
            Lesson::query()->where('slug', '!=', '01-check')->orderBy('position')->get()->mapWithKeys(static fn(Lesson $lesson): array => [$lesson->slug => $lesson->kind])->all(),
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
        File::put("{$this->copy}/test-course/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"x"},"lessons":' . $order . '}');
        File::put("{$this->copy}/test-course/01-basics/01-first/lesson.json", $first);
        File::put("{$this->copy}/test-course/01-basics/02-second/lesson.json", $second);

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('test-course/01-basics/module.json: контрольная')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testEmptyModuleOrCourseIsRejected(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/test-course/01-basics/module.json", '{"title":{"ru":"Основы"},"description":{"ru":"x"},"lessons":[]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('test-course/01-basics/module.json: lessons')
            ->assertFailed();

        File::put("{$this->copy}/test-course/course.json", '{"slug":"test-course","title":{"ru":"x"},"description":{"ru":"x"},"modules":[]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('test-course/course.json: modules')
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
        File::put("{$this->copy}/test-course/01-basics/02-second/lesson.json", '{"title":{"ru":"x"},"kind":' . $kind . ',"steps":[{"type":"quiz","questions":[]}]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('test-course/01-basics/02-second/lesson.json: kind')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testPathsOutsideContentAreRejected(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/test-course/01-basics/module.json", '{"title":{"ru":"x"},"description":{"ru":"x"},"lessons":["../../etc"]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('имена папок')
            ->assertFailed();
    }

    public function testCoursesComeInCatalogOrder(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $first = Course::query()->where('slug', 'test-course')->sole();

        self::assertSame(['ru' => 'Курс для тестов', 'en' => 'Course for tests'], $first->description);
        self::assertSame(['test-course' => 1, 'other-course' => 2], Course::query()->orderBy('position')->pluck('position', 'slug')->all());

        // Порядок в courses.json поменяли — меняется порядок, а не id: на уроки ссылается прогресс
        File::put("{$this->copy}/courses.json", '{"courses":["other-course","test-course"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame(['other-course' => 1, 'test-course' => 2], Course::query()->orderBy('position')->pluck('position', 'slug')->all());
        self::assertSame($first->id, Course::query()->where('slug', 'test-course')->sole()->id);
    }

    public function testCourseLeftOutOfCatalogIsKept(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        File::put("{$this->copy}/courses.json", '{"courses":["test-course"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        // Удалять курс с уроками опасно: прогресс учеников ссылается на них по id. С главной он уходит
        self::assertSame(0, Course::query()->where('slug', 'other-course')->sole()->position);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function brokenCatalogs(): iterable
    {
        yield 'пустой' => ['{"courses":[]}', 'courses.json: courses'];
        yield 'повтор' => ['{"courses":["test-course","test-course"]}', 'courses.json: courses'];
        yield 'путь наружу' => ['{"courses":["../etc"]}', 'имена папок'];
        yield 'нет папки' => ['{"courses":["missing"]}', 'missing/course.json: файла нет'];
    }

    #[DataProvider('brokenCatalogs')]
    public function testBrokenCatalogIsRejected(string $catalog, string $error): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/courses.json", $catalog);

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain($error)
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testCourseSlugMatchesItsFolderAndDescriptionIsRequired(): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/other-course/course.json", '{"slug":"another","title":{"ru":"x"},"description":{"ru":"x"},"modules":["01-only"]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('other-course/course.json: slug')
            ->assertFailed();

        File::put("{$this->copy}/other-course/course.json", '{"slug":"other-course","title":{"ru":"x"},"modules":["01-only"]}');

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain('other-course/course.json: description')
            ->assertFailed();

        self::assertSame(0, Course::query()->count());
    }

    public function testRealCourseImports(): void
    {
        // Настоящий content/ репозитория: формат бэкенда и контента не разошлись
        $this->artisan('course:import')->assertSuccessful();

        self::assertTrue(Course::query()->where('slug', 'csharp')->exists());
    }

    public function testWordsAreSavedWithStableIds(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $lesson = Lesson::query()->where('slug', '01-check')->sole();
        $wear = Word::query()->where('slug', 'wear')->sole();

        self::assertSame(['wear' => 1, 'at-the-moment' => 2], Word::query()->orderBy('position')->pluck('position', 'slug')->all());
        self::assertSame($lesson->id, $wear->lesson_id);
        self::assertSame(['ru' => 'носить (одежду)', 'en' => 'to have clothes on'], $wear->card['translation']);
        // Шаг слов не засчитывается в прогресс
        self::assertSame(['form', 'quiz'], $lesson->countedSteps());

        $directory = $this->app?->make(WordDirectory::class);
        self::assertInstanceOf(WordDirectory::class, $directory);
        self::assertEqualsCanonicalizing([$wear->id => 'wear', $wear->id + 1 => 'at-the-moment'], $directory->wordsOf('other-course'));
        self::assertSame([], $directory->wordsOf('test-course'));

        // Карточку убрали из урока: её строки нет, у оставшейся id прежний, словарь видит это сразу
        $words = json_decode(File::get("{$this->copy}/other-course/01-only/01-check/words.json"), true);
        self::assertIsArray($words);
        File::put("{$this->copy}/other-course/01-only/01-check/words.json", json_encode([$words[0]], JSON_THROW_ON_ERROR));
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame([$wear->id => 'wear'], Word::query()->pluck('slug', 'id')->all());
        self::assertSame([$wear->id => 'wear'], $directory->wordsOf('other-course'));
    }

    public function testWordKeepsIdWhenLessonOrModuleIsRenamed(): void
    {
        $this->copy = $this->copyFixture();
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        $ids = Word::query()->pluck('id', 'slug')->all();

        // Папку урока переименовали, потом — папку модуля: на id карточки ссылается словарь учеников
        File::moveDirectory("{$this->copy}/other-course/01-only/01-check", "{$this->copy}/other-course/01-only/01-final");
        File::put("{$this->copy}/other-course/01-only/module.json", '{"title":{"ru":"М"},"description":{"ru":"М"},"lessons":["01-final"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();
        self::assertSame($ids, Word::query()->pluck('id', 'slug')->all());

        File::moveDirectory("{$this->copy}/other-course/01-only", "{$this->copy}/other-course/01-moved");
        File::put("{$this->copy}/other-course/course.json", '{"slug":"other-course","title":{"ru":"x"},"description":{"ru":"x"},"modules":["01-moved"]}');
        $this->artisan('course:import', ['path' => $this->copy])->assertSuccessful();

        self::assertSame($ids, Word::query()->pluck('id', 'slug')->all());
        $lesson = Lesson::query()->where('slug', '01-final')->sole();
        self::assertSame([$lesson->id], Word::query()->distinct()->pluck('lesson_id')->all());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function brokenWords(): iterable
    {
        yield 'нет id' => ['[{"word":"wear"}]', 'words.json: [0]: id'];
        yield 'id не slug' => ['[{"id":"Wear It","word":"wear"}]', 'words.json: [0]: имена папок'];
        yield 'нет слова' => ['[{"id":"wear"}]', 'words.json: [0]: word'];
        yield 'пустой список' => ['[]', 'words.json: список карточек'];
        yield 'id повторяется в курсе' => ['[{"id":"wear","word":"wear"},{"id":"wear","word":"wear out"}]', 'слово wear уже есть'];
    }

    #[DataProvider('brokenWords')]
    public function testBrokenWordsAreRejected(string $words, string $error): void
    {
        $this->copy = $this->copyFixture();
        File::put("{$this->copy}/other-course/01-only/01-check/words.json", $words);

        $this->artisan('course:import', ['path' => $this->copy])
            ->expectsOutputToContain($error)
            ->assertFailed();

        self::assertSame(0, Word::query()->count());
    }

    private function copyFixture(): string
    {
        $copy = sys_get_temp_dir() . '/course-' . bin2hex(random_bytes(4));
        File::copyDirectory(self::FIXTURE, $copy);

        return $copy;
    }
}
