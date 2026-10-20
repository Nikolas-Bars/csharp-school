<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use App\Modules\User\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property      int         $id
 * @property      string      $name
 * @property      string      $email      он же логин
 * @property      string      $locale     язык из app.supported_locales
 * @property      string      $password   хэш; присваивается открытый пароль — хэшируется сам
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'locale', 'password'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
