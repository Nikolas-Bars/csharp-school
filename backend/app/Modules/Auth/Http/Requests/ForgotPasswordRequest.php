<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => Translator::get('fields.forgot_password.email'),
        ];
    }

    public function toEmail(): string
    {
        return $this->string('email')->toString();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => $this->string('email')->trim()->lower()->toString()]);
    }
}
