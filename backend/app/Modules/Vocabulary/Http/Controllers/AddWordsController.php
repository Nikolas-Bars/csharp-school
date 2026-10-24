<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Modules\Vocabulary\Actions\AddWordsAction;
use App\Modules\Vocabulary\Http\Requests\AddWordsRequest;
use App\Modules\Vocabulary\Http\Resources\VocabularyResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class AddWordsController
{
    public function __construct(private AddWordsAction $addWords) {}

    #[Post('vocabulary/courses/{course}/words', middleware: ['auth:sanctum', 'throttle:vocabulary'])]
    #[OA\Post(
        path: '/api/vocabulary/courses/{course}/words',
        operationId: 'addWords',
        description: 'Слова урока (ученик прошёл карточки) или словарь гостя пачкой после входа (`guest: true` — со своим расписанием). '
            . 'Слово добавляется один раз и ждёт повторения сразу; неизвестные слова пропускаются. Не больше 30 запросов в минуту.',
        summary: 'Добавить слова в словарь',
        security: [['bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AddWordsRequest')),
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
    public function __invoke(AddWordsRequest $request, string $course): JsonResponse
    {
        return ApiResponder::ok(
            VocabularyResource::make($this->addWords->run((int) Auth::id(), $course, $request->toDTOs(), $request->fromGuest())),
            Translator::get('messages.vocabulary.saved'),
        );
    }
}
