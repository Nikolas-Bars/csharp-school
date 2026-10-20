<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Actions\ShowLessonProgressAction;
use App\Modules\Progress\Http\Resources\LessonProgressResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowLessonProgressController
{
    public function __construct(private ShowLessonProgressAction $showProgress) {}

    #[Get('progress/courses/{course}/modules/{module}/lessons/{lesson}', middleware: ['auth:sanctum', 'throttle:progress-read'])]
    #[OA\Get(
        path: '/api/progress/courses/{course}/modules/{module}/lessons/{lesson}',
        operationId: 'showLessonProgress',
        description: 'Пройденные шаги урока и последний зачтённый код задач — чтобы продолжить на любом устройстве. '
            . 'Чтение прогресса — не больше 120 запросов в минуту.',
        summary: 'Прогресс урока',
        security: [['bearer' => []]],
        tags: ['Progress'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Course'),
            new OA\Parameter(ref: '#/components/parameters/Module'),
            new OA\Parameter(ref: '#/components/parameters/Lesson'),
            new OA\Parameter(ref: '#/components/parameters/AcceptLanguage'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Прогресс урока', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/LessonProgress')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(string $course, string $module, string $lesson): JsonResponse
    {
        return ApiResponder::ok(
            LessonProgressResource::make($this->showProgress->run((int) Auth::id(), $course, $module, $lesson)),
            Translator::get('messages.progress.lesson'),
        );
    }
}
