<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\SessionResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;
use stdClass;

final readonly class LoginController
{
    public function __construct(private LoginAction $login) {}

    #[Post('auth/login', middleware: 'throttle:login')]
    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'login',
        description: 'Почта сравнивается без пробелов по краям и без учёта регистра. Не больше 5 попыток в минуту на пару «адрес + почта».',
        summary: 'Вход по почте и паролю',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
        security: [],
        tags: ['Auth'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Вошёл: токен и ученик', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Session')]),
            ])),
            new OA\Response(response: 401, description: 'Неверная почта или пароль — что именно, не говорится', content: new OA\JsonContent(
                ref: '#/components/schemas/ApiError',
                example: ['status' => 'error', 'message' => 'Неверная почта или пароль.', 'data' => null, 'errors' => new stdClass()],
            )),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(LoginRequest $request): JsonResponse
    {
        return ApiResponder::ok(
            SessionResource::make($this->login->run($request->toDTO())),
            Translator::get('messages.auth.logged_in'),
        );
    }
}
