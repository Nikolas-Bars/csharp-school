<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Controllers;

use App\Modules\Course\Actions\ShowLessonAction;
use App\Modules\Course\Http\Resources\LessonResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowLessonController
{
    public function __construct(private ShowLessonAction $showLesson) {}

    #[Get('courses/{course}/modules/{module}/lessons/{lesson}')]
    public function __invoke(string $course, string $module, string $lesson): JsonResponse
    {
        return ApiResponder::ok(
            LessonResource::make($this->showLesson->run($course, $module, $lesson)),
            Translator::get('messages.course.lesson'),
        );
    }
}
