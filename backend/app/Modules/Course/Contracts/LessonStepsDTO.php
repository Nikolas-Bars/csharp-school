<?php

declare(strict_types=1);

namespace App\Modules\Course\Contracts;

use Spatie\LaravelData\Data;

/**
 * Урок для учёта прогресса: id и шаги, которые можно пройти, — id задач и «quiz» (проверка
 * знаний). Теория не засчитывается: её прочитали — и всё.
 */
final class LessonStepsDTO extends Data
{
    public const string QUIZ = 'quiz';

    /**
     * @param list<string> $steps
     */
    public function __construct(
        public int $id,
        public string $module,
        public string $lesson,
        public array $steps,
    ) {}

    /** «01-first-programs/01-hello» — так урок называется во фронте и в запросах */
    public function path(): string
    {
        return "{$this->module}/{$this->lesson}";
    }
}
