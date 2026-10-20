<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\DTO\SessionDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Ответ на регистрацию и вход. Фронт: AuthSession в src/types/api.ts.
 *
 * @property SessionDTO $resource
 */
#[OA\Schema(
    schema: 'Session',
    required: ['user', 'token'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'token', description: 'Для заголовка `Authorization: Bearer <token>`', type: 'string', example: '1|secret-token-example'),
    ],
)]
final class SessionResource extends JsonResource
{
    /**
     * @return array{user: UserResource, token: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'user'  => UserResource::make($this->resource->user),
            'token' => $this->resource->token,
        ];
    }
}
