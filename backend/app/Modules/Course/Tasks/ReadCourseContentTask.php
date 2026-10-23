<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Course\DTO\CourseContentDTO;
use App\Modules\Course\DTO\LessonContentDTO;
use App\Modules\Course\DTO\ModuleContentDTO;
use App\Modules\Course\Enums\LessonKind;
use App\Modules\Course\Exceptions\InvalidContentException;
use JsonException;

/**
 * Читает content/ (формат — content/README.md): список курсов в courses.json и каждый курс из своей
 * папки — так, как его отдаёт API: Markdown уже прочитан, эталонные и неверные решения не берутся вовсе.
 * Пути в ошибках — от content/: «csharp/01-first-programs/module.json».
 *
 * Полную проверку формата делают тесты контента в CI (курс C# — runner/tests/Content.Tests, английский —
 * frontend/src/__tests__/englishContent.spec.ts). Здесь —
 * только то, без чего импорт невозможен, с понятной ошибкой: какой файл и что не так.
 */
final readonly class ReadCourseContentTask
{
    /** Виды упражнений английского: выбор, пропуск, порядок слов, перевод, найди ошибку */
    public const array EXERCISE_KINDS = ['choice' => true, 'gap' => true, 'order' => true, 'translate' => true, 'fix' => true];

    /**
     * @return non-empty-list<CourseContentDTO> курсы в порядке courses.json
     */
    public function run(string $root): array
    {
        $catalog = $this->json($root, 'courses.json');
        $slugs = $this->slugs($catalog, 'courses', 'courses.json');

        if ($slugs === [] || \count($slugs) !== \count(array_unique($slugs))) {
            throw $this->invalid('courses.json', 'courses — папки курсов, хотя бы одна и без повторов');
        }

        return array_map(fn(string $slug, int $index): CourseContentDTO => $this->course($root, $slug, $index + 1), $slugs, array_keys($slugs));
    }

    private function course(string $root, string $directory, int $position): CourseContentDTO
    {
        $file = "{$directory}/course.json";
        $course = $this->json($root, $file);
        $modules = [];

        foreach ($this->slugs($course, 'modules', $file) as $slug) {
            $modules[] = $this->module($root, "{$directory}/{$slug}", $slug);
        }

        if ($modules === []) {
            throw $this->invalid($file, 'modules — в курсе хотя бы один модуль');
        }
        if ($this->string($course, 'slug', $file) !== $directory) {
            throw $this->invalid($file, "slug — как имя папки курса: {$directory}");
        }

        return new CourseContentDTO(
            $directory,
            $this->texts($course, 'title', $file),
            $this->texts($course, 'description', $file),
            $position,
            $modules,
        );
    }

    private function module(string $root, string $directory, string $slug): ModuleContentDTO
    {
        $file = "{$directory}/module.json";
        $module = $this->json($root, $file);
        $lessons = [];

        foreach ($this->slugs($module, 'lessons', $file) as $lesson) {
            $lessons[] = $this->lesson($root, "{$directory}/{$lesson}", $lesson);
        }

        if ($lessons === []) {
            throw $this->invalid($file, 'lessons — в модуле хотя бы один урок');
        }
        $this->moduleTest($lessons, $file);

        return new ModuleContentDTO($slug, $this->texts($module, 'title', $file), $this->texts($module, 'description', $file), $lessons);
    }

    private function lesson(string $root, string $directory, string $slug): LessonContentDTO
    {
        $file = "{$directory}/lesson.json";
        $lesson = $this->json($root, $file);
        $steps = [];

        foreach ($this->list($lesson, 'steps', $file) as $index => $step) {
            $where = "{$file}: steps[{$index}]";
            $steps[] = match ($this->string($step, 'type', $where)) {
                'theory'   => ['type' => 'theory', 'text' => $this->markdown($root, $directory, $this->texts($step, 'text', $where), $where)],
                'task'     => $this->task($root, "{$directory}/tasks/" . $this->slug($this->string($step, 'task', $where), $where)),
                'practice' => [
                    'type'      => 'practice',
                    'id'        => $this->slug($this->string($step, 'id', $where), $where),
                    'exercises' => $this->exercises($root, $directory, $step, $where),
                ],
                // Вопросы с выбором варианта — прямо в lesson.json (курс C#), упражнения — файлом (английский)
                'quiz' => \is_array($step) && \array_key_exists('file', $step)
                    ? ['type' => 'quiz', 'exercises' => $this->exercises($root, $directory, $step, $where)]
                    : ['type' => 'quiz', 'questions' => $this->list($step, 'questions', $where)],
                default => throw $this->invalid($where, 'type: theory, task, practice или quiz'),
            };
        }

        $title = $this->texts($lesson, 'title', $file);
        $this->countedSteps($steps, $file);

        return new LessonContentDTO($slug, $title, $steps, $this->kind($lesson, $file));
    }

    /**
     * Вид урока: «kind» необязателен — без него обычный урок, «test» — контрольная модуля.
     *
     * @param array<array-key, mixed> $lesson
     */
    private function kind(array $lesson, string $file): LessonKind
    {
        if (!\array_key_exists('kind', $lesson)) {
            return LessonKind::Lesson;
        }
        $kind = $lesson['kind'];

        return (\is_string($kind) ? LessonKind::tryFrom($kind) : null) ?? throw $this->invalid($file, 'kind: lesson или test');
    }

    /**
     * Модуль заканчивается контрольной и пройден только после неё (rules/course-content.md):
     * контрольная в модуле одна и стоит последней.
     *
     * @param non-empty-list<LessonContentDTO> $lessons
     */
    private function moduleTest(array $lessons, string $file): void
    {
        $tests = array_filter($lessons, static fn(LessonContentDTO $lesson): bool => $lesson->kind === LessonKind::Test);

        if (\count($tests) !== 1 || $lessons[array_key_last($lessons)]->kind !== LessonKind::Test) {
            throw $this->invalid($file, 'контрольная («kind»: «test») в модуле одна и последняя');
        }
    }

    /**
     * Прогресс считает урок пройденным, когда пройдены все задачи, практики и проверка знаний: у
     * каждой задачи и практики свой id, «quiz» — только у проверки, и она в уроке одна (без неё урок
     * не пройти).
     *
     * @param list<array<string, mixed>> $steps
     */
    private function countedSteps(array $steps, string $file): void
    {
        /** @var list<string> $ids id задачи — имя её папки, id практики — slug, всегда строка */
        $ids = array_column(array_filter($steps, static fn(array $step): bool => $step['type'] === 'task' || $step['type'] === 'practice'), 'id');
        $quizzes = \count(array_filter($steps, static fn(array $step): bool => $step['type'] === 'quiz'));

        if ($quizzes !== 1) {
            throw $this->invalid($file, 'в уроке ровно одна проверка знаний (quiz)');
        }
        if (\in_array(LessonStepsDTO::QUIZ, $ids, true) || \count($ids) !== \count(array_unique($ids))) {
            throw $this->invalid($file, 'id задач и практик урока — разные и не «quiz»');
        }
    }

    /**
     * Упражнения английского (практика или проверка знаний) — из файла рядом с уроком: список, у
     * каждого известный «kind». Остальные поля упражнения проверяют тесты контента фронта — той же
     * функцией, что проверяет ответ ученика (frontend/src/utils/answers.ts).
     *
     * @return list<array<array-key, mixed>>
     */
    private function exercises(string $root, string $directory, mixed $step, string $where): array
    {
        $name = $this->string($step, 'file', $where);
        if (preg_match('#^[a-z0-9][a-z0-9-]*(/[a-z0-9][a-z0-9-]*)?\.json$#', $name) !== 1) {
            throw $this->invalid($where, "file — JSON рядом с уроком или в его подпапке: practice/form.json, не {$name}");
        }
        $file = "{$directory}/{$name}";
        $exercises = $this->json($root, $file);

        if ($exercises === [] || !array_is_list($exercises)) {
            throw $this->invalid($file, 'список упражнений, хотя бы одно');
        }

        return array_map(function (mixed $exercise, int $index) use ($file): array {
            $kind = \is_array($exercise) ? ($exercise['kind'] ?? null) : null;

            return \is_array($exercise) && \is_string($kind) && isset(self::EXERCISE_KINDS[$kind])
                ? $exercise
                : throw $this->invalid("{$file}: [{$index}]", 'kind: ' . implode(', ', array_keys(self::EXERCISE_KINDS)));
        }, $exercises, array_keys($exercises));
    }

    /**
     * Задача для ученика: условие, стартовый код, проверки (их выполняет runner в браузере),
     * сообщения о провале и подсказки. solution и wrong — только для тестов контента.
     *
     * @return array<string, mixed>
     */
    private function task(string $root, string $directory): array
    {
        $file = "{$directory}/task.json";
        $task = $this->json($root, $file);

        return [
            'type'      => 'task',
            'id'        => basename($directory),
            'title'     => $this->texts($task, 'title', $file),
            'statement' => $this->markdown($root, $directory, $this->texts($task, 'statement', $file), $file),
            'starter'   => $this->read($root, $directory . '/' . $this->string($task, 'starter', $file)),
            'checks'    => $this->list($task, 'checks', $file),
            'messages'  => $this->list($task, 'messages', $file),
            'hints'     => $this->list($task, 'hints', $file),
        ];
    }

    /**
     * @param array<string, string> $files язык → имя .md рядом
     *
     * @return array<string, string> язык → текст
     */
    private function markdown(string $root, string $directory, array $files, string $where): array
    {
        $texts = [];

        foreach ($files as $locale => $name) {
            if (str_contains($name, '/') || str_contains($name, '\\')) {
                throw $this->invalid($where, "файл {$name} — только имя рядом с JSON");
            }
            $texts[$locale] = $this->read($root, "{$directory}/{$name}");
        }

        return $texts;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function json(string $root, string $file): array
    {
        try {
            $value = json_decode($this->read($root, $file), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw $this->invalid($file, $exception->getMessage());
        }

        return \is_array($value) ? $value : throw $this->invalid($file, 'ожидался объект JSON');
    }

    private function read(string $root, string $file): string
    {
        $path = rtrim($root, '/') . '/' . $file;
        $text = is_file($path) ? file_get_contents($path) : false;

        return $text === false ? throw $this->invalid($file, 'файла нет') : $text;
    }

    private function string(mixed $data, string $key, string $where): string
    {
        $value = \is_array($data) ? ($data[$key] ?? null) : null;

        return \is_string($value) && $value !== '' ? $value : throw $this->invalid($where, "{$key} — непустая строка");
    }

    /**
     * Тексты по языкам; русский обязателен.
     *
     * @return array<string, string>
     */
    private function texts(mixed $data, string $key, string $where): array
    {
        $value = \is_array($data) ? ($data[$key] ?? null) : null;
        $texts = \is_array($value) ? array_filter($value, \is_string(...)) : [];

        if (!\is_string($texts['ru'] ?? null) || \count($texts) !== \count((array) $value)) {
            throw $this->invalid($where, "{$key} — тексты по языкам, русский обязателен: {\"ru\": \"…\"}");
        }

        /** @var array<string, string> $texts */
        return $texts;
    }

    /**
     * @return list<mixed>
     */
    private function list(mixed $data, string $key, string $where): array
    {
        $value = \is_array($data) ? ($data[$key] ?? null) : null;

        return \is_array($value) && array_is_list($value) ? $value : throw $this->invalid($where, "{$key} — список");
    }

    /**
     * Папки курсов, модулей и уроков: только «01-first-programs», без путей наружу.
     *
     * @return list<string>
     */
    private function slugs(mixed $data, string $key, string $where): array
    {
        $slugs = $this->list($data, $key, $where);

        return array_map(fn(mixed $slug): string => $this->slug($slug, $where), $slugs);
    }

    /** Имя папки: «csharp», «01-first-programs», «hello-world» — без путей наружу */
    private function slug(mixed $slug, string $where): string
    {
        return \is_string($slug) && preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $slug) === 1
            ? $slug
            : throw $this->invalid($where, 'имена папок — строчные латинские буквы, цифры и дефис: 01-first-programs');
    }

    private function invalid(string $where, string $reason): InvalidContentException
    {
        return new InvalidContentException(['where' => $where, 'reason' => $reason]);
    }
}
