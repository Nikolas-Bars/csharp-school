<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ссылка «Забыли пароль?»: одна на почту, новая заменяет старую.
 *
 * @property string      $email
 * @property string      $token      SHA-256 токена из ссылки
 * @property Carbon|null $created_at
 */
final class PasswordResetToken extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'password_reset_tokens';

    protected $primaryKey = 'email';

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
