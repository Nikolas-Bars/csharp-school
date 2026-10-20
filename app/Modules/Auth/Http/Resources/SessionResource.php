<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\DTO\SessionDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ответ на регистрацию и вход. Фронт: AuthSession в src/types/api.ts.
 *
 * @property SessionDTO $resource
 */
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
