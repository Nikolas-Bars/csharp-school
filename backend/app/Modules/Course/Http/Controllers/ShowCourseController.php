<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Controllers;

use App\Modules\Course\Actions\ShowCourseAction;
use App\Modules\Course\Http\Resources\CourseResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowCourseController
{
    public function __construct(private ShowCourseAction $showCourse) {}

    /** Открыто всем: учиться можно без аккаунта */
    #[Get('courses/{course}')]
    public function __invoke(string $course): JsonResponse
    {
        return ApiResponder::ok(CourseResource::make($this->showCourse->run($course)), Translator::get('messages.course.course'));
    }
}
