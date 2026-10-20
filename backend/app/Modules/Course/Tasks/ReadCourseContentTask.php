<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use App\Modules\Course\DTO\CourseContentDTO;
use App\Modules\Course\DTO\LessonContentDTO;
use App\Modules\Course\DTO\ModuleContentDTO;
use App\Modules\Course\Exceptions\InvalidContentException;
use JsonException;

/**
 * Читает курс из content/ (формат — content/README.md) и собирает уроки так, как их отдаёт API:
 * Markdown уже прочитан, эталонные и неверные решения не берутся вовсе.
 *
 * Полную проверку формата делают тесты контента (runner/tests/Content.Tests) в CI. Здесь —
 * только то, без чего импорт невозможен, с понятной ошибкой: какой файл и что не так.
 */
final readonly class ReadCourseContentTask
{
    public function run(string $root): CourseContentDTO
    {
        $course = $this->json($root, 'course.json');
        $modules = [];

        foreach ($this->slugs($course, 'modules', 'course.json') as $slug) {
            $modules[] = $this->module($root, $slug);
        }

        return new CourseContentDTO(
            $this->slug($this->string($course, 'slug', 'course.json'), 'course.json'),
            $this->texts($course, 'title', 'course.json'),
            $modules,
        );
    }

    private function module(string $root, string $slug): ModuleContentDTO
    {
        $file = "{$slug}/module.json";
        $module = $this->json($root, $file);
        $lessons = [];

        foreach ($this->slugs($module, 'lessons', $file) as $lesson) {
            $lessons[] = $this->lesson($root, "{$slug}/{$lesson}", $lesson);
        }

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
                'theory' => ['type' => 'theory', 'text' => $this->markdown($root, $directory, $this->texts($step, 'text', $where), $where)],
                'task'   => $this->task($root, "{$directory}/tasks/" . $this->slug($this->string($step, 'task', $where), $where)),
                'quiz'   => ['type' => 'quiz', 'questions' => $this->list($step, 'questions', $where)],
                default  => throw $this->invalid($where, 'type: theory, task или quiz'),
            };
        }

        return new LessonContentDTO($slug, $this->texts($lesson, 'title', $file), $steps);
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
     * Папки модулей и уроков: только «01-first-programs», без путей наружу.
     *
     * @return list<string>
     */
    private function slugs(mixed $data, string $key, string $where): array
    {
        $slugs = $this->list($data, $key, $where);

        return array_map(fn(mixed $slug): string => $this->slug($slug, $where), $slugs);
    }

    /** Имя папки: «01-first-programs», «hello-world» — без путей наружу */
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
