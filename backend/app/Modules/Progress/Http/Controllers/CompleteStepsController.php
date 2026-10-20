<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Actions\CompleteStepsAction;
use App\Modules\Progress\Http\Requests\CompleteStepsRequest;
use App\Modules\Progress\Http\Resources\CourseProgressResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class CompleteStepsController
{
    public function __construct(private CompleteStepsAction $completeSteps) {}

    #[Post('progress/courses/{course}/steps', middleware: ['auth:sanctum', 'throttle:progress'])]
    public function __invoke(CompleteStepsRequest $request, string $course): JsonResponse
    {
        return ApiResponder::ok(
            CourseProgressResource::make($this->completeSteps->run((int) Auth::id(), $course, $request->toDTOs(), $request->fromGuest())),
            Translator::get('messages.progress.saved'),
        );
    }
}
