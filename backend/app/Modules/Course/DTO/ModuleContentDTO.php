<?php

declare(strict_types=1);

namespace App\Modules\Course\DTO;

use Spatie\LaravelData\Data;

final class ModuleContentDTO extends Data
{
    /**
     * @param array<string, string>  $title
     * @param array<string, string>  $description
     * @param list<LessonContentDTO> $lessons
     */
    public function __construct(
        public string $slug,
        public array $title,
        public array $description,
        public array $lessons,
    ) {}
}
