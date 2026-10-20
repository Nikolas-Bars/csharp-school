<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Actions\ShowCourseProgressAction;
use App\Modules\Progress\Http\Resources\CourseProgressResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowCourseProgressController
{
    public function __construct(private ShowCourseProgressAction $showProgress) {}

    #[Get('progress/courses/{course}', middleware: ['auth:sanctum', 'throttle:progress-read'])]
    #[OA\Get(
        path: '/api/progress/courses/{course}',
        operationId: 'showCourseProgress',
        description: 'Какие шаги каждого урока пройдены — для карты курса, без кода решений. '
            . 'Нет такого курса — пустой список уроков. Чтение прогресса — не больше 120 запросов в минуту.',
        summary: 'Прогресс по курсу',
        security: [['bearer' => []]],
        tags: ['Progress'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Course'), new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Прогресс по урокам курса', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CourseProgress')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(string $course): JsonResponse
    {
        return ApiResponder::ok(
            CourseProgressResource::make($this->showProgress->run((int) Auth::id(), $course)),
            Translator::get('messages.progress.course'),
        );
    }
}
