<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Actions\ShowCourseProgressAction;
use App\Modules\Progress\Http\Resources\CourseProgressResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowCourseProgressController
{
    public function __construct(private ShowCourseProgressAction $showProgress) {}

    #[Get('progress/courses/{course}', middleware: ['auth:sanctum', 'throttle:progress-read'])]
    public function __invoke(string $course): JsonResponse
    {
        return ApiResponder::ok(
            CourseProgressResource::make($this->showProgress->run((int) Auth::id(), $course)),
            Translator::get('messages.progress.course'),
        );
    }
}
