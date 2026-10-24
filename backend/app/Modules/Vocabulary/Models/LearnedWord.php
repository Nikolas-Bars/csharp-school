<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $user_id
 * @property      int         $word_id     ID карточки слова модуля Course
 * @property      int         $box         коробка повторения: 0 — новое или забытое … 4 — выучено
 * @property      Carbon      $due_at      когда повторить
 * @property      Carbon|null $reviewed_at последнее повторение
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
final class LearnedWord extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'word_id', 'box', 'due_at', 'reviewed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['box' => 'integer', 'due_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
