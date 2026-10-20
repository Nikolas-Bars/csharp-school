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
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class CompleteStepsController
{
    public function __construct(private CompleteStepsAction $completeSteps) {}

    #[Post('progress/courses/{course}/steps', middleware: ['auth:sanctum', 'throttle:progress'])]
    #[OA\Post(
        path: '/api/progress/courses/{course}/steps',
        operationId: 'completeSteps',
        description: 'Один шаг (задача только что решена) или прогресс гостя пачкой после входа (`guest: true`). '
            . 'Результат проверки присылает браузер, поэтому шаг засчитывается один раз, неизвестные уроки и шаги пропускаются, '
            . 'шаг без кода сохранённый код не стирает. Не больше 30 запросов в минуту.',
        summary: 'Засчитать пройденные шаги',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CompleteStepsRequest')),
        tags: ['Progress'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Course'), new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Сохранено: прогресс по курсу после записи', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CourseProgress')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(CompleteStepsRequest $request, string $course): JsonResponse
    {
        return ApiResponder::ok(
            CourseProgressResource::make($this->completeSteps->run((int) Auth::id(), $course, $request->toDTOs(), $request->fromGuest())),
            Translator::get('messages.progress.saved'),
        );
    }
}
