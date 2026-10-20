<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\ResetPasswordAction;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;
use stdClass;

final readonly class ResetPasswordController
{
    public function __construct(private ResetPasswordAction $resetPassword) {}

    #[Post('auth/reset-password', middleware: 'throttle:password-reset')]
    #[OA\Post(
        path: '/api/auth/reset-password',
        operationId: 'resetPassword',
        description: 'Ссылка одноразовая; после смены пароля все токены входа ученика удаляются. Не больше 10 попыток в минуту с адреса и 5 на одну почту.',
        summary: 'Новый пароль по ссылке из письма',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ResetPasswordRequest')),
        security: [],
        tags: ['Auth'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Пароль изменён', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', type: 'null')]),
            ])),
            new OA\Response(response: 400, description: 'Ссылка не подходит: устарела, уже использована или почта другая — что именно, не говорится', content: new OA\JsonContent(
                ref: '#/components/schemas/ApiError',
                example: ['status' => 'error', 'message' => 'Ссылка для нового пароля не подходит: она устарела или уже использована. Запроси новую.', 'data' => null, 'errors' => new stdClass()],
            )),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->resetPassword->run($request->toDTO());

        return ApiResponder::ok(null, Translator::get('messages.auth.password_reset'));
    }
}
