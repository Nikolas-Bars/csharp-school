<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use App\Modules\User\Contracts\UserDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Ученик в ответах Auth. Фронт: User в src/types/api.ts.
 *
 * @property UserDTO $resource
 */
#[OA\Schema(
    schema: 'User',
    description: 'Ученик',
    required: ['id', 'nickname', 'email', 'locale'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nickname', type: 'string', example: 'Anya'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'anya@example.com'),
        new OA\Property(property: 'locale', description: 'Язык аккаунта', type: 'string', enum: ['ru', 'en'], example: 'ru'),
    ],
)]
final class UserResource extends JsonResource
{
    /**
     * @return array{id: int, nickname: string, email: string, locale: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->resource->id,
            'nickname' => $this->resource->nickname,
            'email'    => $this->resource->email,
            'locale'   => $this->resource->locale,
        ];
    }
}
