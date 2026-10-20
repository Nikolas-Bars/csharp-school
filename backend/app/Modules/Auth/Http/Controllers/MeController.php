<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\CurrentUserAction;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class MeController
{
    public function __construct(private CurrentUserAction $currentUser) {}

    #[Get('auth/me', middleware: 'auth:sanctum')]
    #[OA\Get(
        path: '/api/auth/me',
        operationId: 'me',
        summary: 'Текущий ученик',
        security: [['bearer' => []]],
        tags: ['Auth'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/AcceptLanguage')],
        responses: [
            new OA\Response(response: 200, description: 'Ученик, которому выдан токен', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        return ApiResponder::ok(
            UserResource::make($this->currentUser->run((int) Auth::id())),
            Translator::get('messages.auth.me'),
        );
    }
}
