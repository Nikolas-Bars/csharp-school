<?php

declare(strict_types=1);

namespace App\Modules\Course\Actions;

use App\Modules\Course\Exceptions\CourseNotFoundException;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Repositories\CourseRepository;

final readonly class ShowCourseAction
{
    public function __construct(private CourseRepository $courses) {}

    public function run(string $slug): Course
    {
        return $this->courses->findWithStructure($slug) ?? throw new CourseNotFoundException();
    }
}
