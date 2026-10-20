<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\DTO\RegisterDTO;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'min:2', 'max:32'],
            'email'    => ['required', 'string', 'email', 'max:254'],
            // bcrypt учитывает только первые 72 байта
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nickname' => Translator::get('fields.register.nickname'),
            'email'    => Translator::get('fields.register.email'),
            'password' => Translator::get('fields.register.password'),
        ];
    }

    public function toDTO(): RegisterDTO
    {
        return new RegisterDTO(
            $this->string('nickname')->toString(),
            $this->string('email')->toString(),
            $this->string('password')->toString(),
        );
    }

    /** Почта без пробелов и в нижнем регистре: Anya@Mail.ru и anya@mail.ru — один аккаунт */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nickname' => $this->string('nickname')->trim()->toString(),
            'email'    => $this->string('email')->trim()->lower()->toString(),
        ]);
    }
}
