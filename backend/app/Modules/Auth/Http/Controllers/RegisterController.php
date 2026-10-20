<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\RegisterAction;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\SessionResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class RegisterController
{
    public function __construct(private RegisterAction $register) {}

    #[Post('auth/register', middleware: 'throttle:auth')]
    #[OA\Post(
        path: '/api/auth/register',
        operationId: 'register',
        description: 'Регистрация и сразу вход: в ответе токен. Язык аккаунта — язык запроса (`Accept-Language`). '
            . 'Почта уже занята — 422 с ошибкой поля `email`. Не больше 10 регистраций в минуту с одного адреса.',
        summary: 'Регистрация',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')),
        security: [],
        tags: ['Auth'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 201, description: 'Аккаунт создан: токен и ученик', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Session')]),
            ])),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        return ApiResponder::created(
            SessionResource::make($this->register->run($request->toDTO())),
            Translator::get('messages.auth.registered'),
        );
    }
}
