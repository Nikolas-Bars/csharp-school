<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Controllers;

use App\Modules\Course\Actions\ListWordsAction;
use App\Modules\Course\Http\Resources\WordResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ListWordsController
{
    public function __construct(private ListWordsAction $listWords) {}

    /** Открыто всем: словарь гостя живёт в браузере, карточки — отсюда */
    #[Get('courses/{course}/words')]
    #[OA\Get(
        path: '/api/courses/{course}/words',
        operationId: 'listWords',
        description: 'Карточки слов курса по порядку уроков, с уроком, где слово появилось. Тексты — на языке запроса. '
            . 'Открыто всем, вход не нужен. В курсе без карточек — пустой список.',
        summary: 'Слова курса',
        security: [],
        tags: ['Course'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Course'),
            new OA\Parameter(ref: '#/components/parameters/Cursor'),
            new OA\Parameter(ref: '#/components/parameters/AcceptLanguage'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Страница слов', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', required: ['items', 'next_cursor'], properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CourseWord')),
                    new OA\Property(property: 'next_cursor', description: 'Курсор следующей страницы; на последней — null', type: ['string', 'null']),
                ], type: 'object')]),
            ])),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function __invoke(Request $request, string $course): JsonResponse
    {
        $cursor = $request->query('cursor');

        return ApiResponder::page($this->listWords->run($course, \is_string($cursor) ? $cursor : null), WordResource::class, Translator::get('messages.course.words'));
    }
}
