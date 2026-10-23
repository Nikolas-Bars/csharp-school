<?php

declare(strict_types=1);

namespace App\Modules\Course\DTO;

use Spatie\LaravelData\Data;

/** Курс, прочитанный из content/: то, что попадёт в базу */
final class CourseContentDTO extends Data
{
    /**
     * @param array<string, string>  $title
     * @param array<string, string>  $description
     * @param int                    $position    место в courses.json, с 1: порядок на главной
     * @param list<ModuleContentDTO> $modules
     */
    public function __construct(
        public string $slug,
        public array $title,
        public array $description,
        public int $position,
        public array $modules,
    ) {}
}
