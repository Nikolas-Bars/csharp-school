<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Actions\ShowLessonProgressAction;
use App\Modules\Progress\Http\Resources\LessonProgressResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowLessonProgressController
{
    public function __construct(private ShowLessonProgressAction $showProgress) {}

    #[Get('progress/courses/{course}/modules/{module}/lessons/{lesson}', middleware: ['auth:sanctum', 'throttle:progress-read'])]
    public function __invoke(string $course, string $module, string $lesson): JsonResponse
    {
        return ApiResponder::ok(
            LessonProgressResource::make($this->showProgress->run((int) Auth::id(), $course, $module, $lesson)),
            Translator::get('messages.progress.lesson'),
        );
    }
}
