<?php

declare(strict_types=1);

namespace App\Modules\Course\DTO;

use App\Modules\Course\Enums\LessonKind;
use Spatie\LaravelData\Data;

final class LessonContentDTO extends Data
{
    /**
     * @param array<string, string>      $title
     * @param list<array<string, mixed>> $steps шаги урока для API: тексты уже прочитаны из .md, эталонов нет
     */
    public function __construct(
        public string $slug,
        public array $title,
        public array $steps,
        public LessonKind $kind = LessonKind::Lesson,
    ) {}

    /**
     * Карточки слов урока (шаги words) по порядку.
     *
     * @return list<array<array-key, mixed>>
     */
    public function cards(): array
    {
        $cards = [];

        foreach ($this->steps as $step) {
            if ($step['type'] === 'words' && \is_array($step['words'])) {
                array_push($cards, ...array_values(array_filter($step['words'], \is_array(...))));
            }
        }

        return $cards;
    }
}
