<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\DTO\LoginDTO;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 254, example: 'student@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', maxLength: 72, example: 'Password123'),
    ],
)]
final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email'    => Translator::get('fields.login.email'),
            'password' => Translator::get('fields.login.password'),
        ];
    }

    public function toDTO(): LoginDTO
    {
        return new LoginDTO($this->string('email')->toString(), $this->string('password')->toString());
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => $this->string('email')->trim()->lower()->toString()]);
    }
}
