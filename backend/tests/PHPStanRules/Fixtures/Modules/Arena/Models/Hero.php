<?php

declare(strict_types=1);

namespace App\Modules\Arena\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Hero extends Model
{
    /**
     * @return HasMany<Hero, $this>
     */
    public function allies(): HasMany
    {
        // Модели можно строить запросы: связи и scope
        return $this->hasMany(self::class)->where('hp', '>', 0);
    }
}
