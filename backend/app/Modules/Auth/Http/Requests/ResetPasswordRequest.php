<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ResetPasswordRequest',
    required: ['token', 'email', 'password'],
    properties: [
        new OA\Property(property: 'token', description: 'Из ссылки в письме', type: 'string', maxLength: 255),
        new OA\Property(property: 'email', description: 'Из ссылки в письме', type: 'string', format: 'email', maxLength: 254, example: 'student@example.com'),
        new OA\Property(property: 'password', description: 'Как при регистрации; bcrypt учитывает только первые 72 байта', type: 'string', format: 'password', maxLength: 72, minLength: 8, example: 'Password123'),
    ],
)]
final class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'token'    => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:254'],
            'password' => RegisterRequest::PASSWORD_RULES,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'token'    => Translator::get('fields.reset_password.token'),
            'email'    => Translator::get('fields.reset_password.email'),
            'password' => Translator::get('fields.reset_password.password'),
        ];
    }

    public function toDTO(): ResetPasswordDTO
    {
        return new ResetPasswordDTO(
            $this->string('email')->toString(),
            $this->string('token')->toString(),
            $this->string('password')->toString(),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => $this->string('email')->trim()->lower()->toString()]);
    }
}
