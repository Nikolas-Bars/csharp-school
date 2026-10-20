<?php

declare(strict_types=1);

namespace App\Modules\Progress\DTO;

use Spatie\LaravelData\Data;

final class LessonProgressDTO extends Data
{
    /**
     * @param list<string>          $steps     все засчитываемые шаги урока по порядку
     * @param list<string>          $completed пройденные из них
     * @param array<string, string> $solutions задача → последний зачтённый код
     */
    public function __construct(
        public string $module,
        public string $lesson,
        public array $steps,
        public array $completed,
        public array $solutions,
    ) {}

    /** Урок пройден, когда пройдены все его задачи и проверка знаний */
    public function done(): bool
    {
        return $this->steps !== [] && array_diff($this->steps, $this->completed) === [];
    }
}
