<?php

declare(strict_types=1);

namespace App\Modules\Progress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $user_id
 * @property      int         $lesson_id ID урока модуля Course
 * @property      string      $step      id задачи или «quiz»
 * @property      string|null $code      последний зачтённый код задачи
 * @property      Carbon      $completed_at
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
final class CompletedStep extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'lesson_id', 'step', 'code', 'completed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
