<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Modules\Vocabulary\Actions\ShowVocabularyAction;
use App\Modules\Vocabulary\Http\Resources\VocabularyResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowVocabularyController
{
    public function __construct(private ShowVocabularyAction $showVocabulary) {}

    #[Get('vocabulary/courses/{course}', middleware: ['auth:sanctum', 'throttle:vocabulary-read'])]
    #[OA\Get(
        path: '/api/vocabulary/courses/{course}',
        operationId: 'showVocabulary',
        description: 'Выученные слова курса и когда их повторять. Карточки — `GET /api/courses/{course}/words`. '
            . 'Нет такого курса — пустой словарь. Не больше 120 запросов в минуту.',
        summary: 'Мой словарь',
        security: [['bearer' => []]],
        tags: ['Vocabulary'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Course'), new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Словарь по курсу', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Vocabulary')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(string $course): JsonResponse
    {
        return ApiResponder::ok(VocabularyResource::make($this->showVocabulary->run((int) Auth::id(), $course)), Translator::get('messages.vocabulary.words'));
    }
}
