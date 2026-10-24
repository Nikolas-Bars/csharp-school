<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Modules\Vocabulary\Actions\ReviewWordsAction;
use App\Modules\Vocabulary\Http\Requests\ReviewWordsRequest;
use App\Modules\Vocabulary\Http\Resources\VocabularyResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class ReviewWordsController
{
    public function __construct(private ReviewWordsAction $reviewWords) {}

    #[Post('vocabulary/courses/{course}/reviews', middleware: ['auth:sanctum', 'throttle:vocabulary'])]
    #[OA\Post(
        path: '/api/vocabulary/courses/{course}/reviews',
        operationId: 'reviewWords',
        description: 'Итоги повторения: «Помню» — следующая коробка (повторить через 3, 7, 14, 30 дней), «Не помню» — коробка 0, '
            . 'повторить завтра. Засчитываются только слова из словаря, которые пора повторять (с запасом в сутки на часовой пояс); '
            . 'одно слово в пачке — один раз. Не больше 30 запросов в минуту.',
        summary: 'Сохранить повторение',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReviewWordsRequest')),
        tags: ['Vocabulary'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Course'), new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Сохранено: словарь по курсу после записи', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Vocabulary')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(ReviewWordsRequest $request, string $course): JsonResponse
    {
        return ApiResponder::ok(
            VocabularyResource::make($this->reviewWords->run((int) Auth::id(), $course, $request->toDTOs())),
            Translator::get('messages.vocabulary.reviewed'),
        );
    }
}
