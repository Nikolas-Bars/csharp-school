<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use App\Modules\User\Contracts\UserDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ученик в ответах Auth. Фронт: User в src/types/api.ts.
 *
 * @property UserDTO $resource
 */
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
