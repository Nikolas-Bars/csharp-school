<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Controllers;

use App\Modules\Course\Actions\ListCoursesAction;
use App\Modules\Course\Http\Resources\CourseSummaryResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ListCoursesController
{
    public function __construct(private ListCoursesAction $listCourses) {}

    /** Открыто всем: с выбора курса начинается главная */
    #[Get('courses')]
    #[OA\Get(
        path: '/api/courses',
        operationId: 'listCourses',
        description: 'Курсы по порядку, тексты — на языке запроса. Открыто всем, вход не нужен.',
        summary: 'Курсы платформы',
        security: [],
        tags: ['Course'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Cursor'), new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Страница курсов', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', required: ['items', 'next_cursor'], properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CourseSummary')),
                    new OA\Property(property: 'next_cursor', description: 'Курсор следующей страницы; на последней — null', type: ['string', 'null']),
                ], type: 'object')]),
            ])),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $cursor = $request->query('cursor');

        return ApiResponder::page($this->listCourses->run(\is_string($cursor) ? $cursor : null), CourseSummaryResource::class, Translator::get('messages.course.courses'));
    }
}
