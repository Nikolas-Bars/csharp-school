<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\LogoutAction;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class LogoutController
{
    public function __construct(private LogoutAction $logout) {}

    #[Post('auth/logout', middleware: 'auth:sanctum')]
    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'logout',
        description: 'Отзывает только токен этого запроса: на других устройствах вход остаётся.',
        summary: 'Выход на этом устройстве',
        security: [['bearer' => []]],
        tags: ['Auth'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Вышел', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', type: 'null')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        // Токен «{id}|{секрет}» уже проверен auth:sanctum; удаляется он по id и только у своего владельца
        $tokenId = (int) Str::before((string) $request->bearerToken(), '|');

        $this->logout->run($tokenId, (int) Auth::id());

        return ApiResponder::ok(null, Translator::get('messages.auth.logged_out'));
    }
}
