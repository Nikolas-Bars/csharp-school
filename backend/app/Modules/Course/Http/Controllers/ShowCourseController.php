<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Controllers;

use App\Modules\Course\Actions\ShowCourseAction;
use App\Modules\Course\Http\Resources\CourseResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowCourseController
{
    public function __construct(private ShowCourseAction $showCourse) {}

    /** Открыто всем: учиться можно без аккаунта */
    #[Get('courses/{course}')]
    #[OA\Get(
        path: '/api/courses/{course}',
        operationId: 'showCourse',
        description: 'Модули и уроки без содержимого, тексты — на языке запроса. Открыто всем, вход не нужен.',
        summary: 'Карта курса',
        security: [],
        tags: ['Course'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Course'), new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Карта курса', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Course')]),
            ])),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function __invoke(string $course): JsonResponse
    {
        return ApiResponder::ok(CourseResource::make($this->showCourse->run($course)), Translator::get('messages.course.course'));
    }
}
