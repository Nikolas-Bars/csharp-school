<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\SendPasswordResetLinkAction;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class ForgotPasswordController
{
    public function __construct(private SendPasswordResetLinkAction $sendLink) {}

    #[Post('auth/forgot-password', middleware: 'throttle:password-reset-link')]
    #[OA\Post(
        path: '/api/auth/forgot-password',
        operationId: 'forgotPassword',
        description: 'Ответ одинаковый для любой почты, а поиск аккаунта и отправка письма идут после ответа — по ответу не понять, есть ли аккаунт. Ссылка в письме ведёт на сайт (/reset-password?token=…&email=…) и действует 60 минут. Не больше 5 запросов в минуту с адреса и 3 за 10 минут на одну почту.',
        summary: 'Письмо со ссылкой для нового пароля',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ForgotPasswordRequest')),
        security: [],
        tags: ['Auth'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Запрос принят (письмо — только если аккаунт есть)', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', type: 'null')]),
            ])),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $this->sendLink->run($request->toEmail());

        // Один ответ для любой почты: есть ли такой аккаунт, не говорим
        return ApiResponder::ok(null, Translator::get('messages.auth.reset_link_sent'));
    }
}
