<?php

declare(strict_types=1);

namespace App\Modules\Course\Actions;

use App\Modules\Course\Exceptions\LessonNotFoundException;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Repositories\LessonRepository;

final readonly class ShowLessonAction
{
    public function __construct(private LessonRepository $lessons) {}

    public function run(string $course, string $module, string $lesson): Lesson
    {
        return $this->lessons->findByPath($course, $module, $lesson) ?? throw new LessonNotFoundException();
    }
}
