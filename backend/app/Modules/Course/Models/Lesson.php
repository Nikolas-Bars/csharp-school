<?php

declare(strict_types=1);

namespace App\Modules\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                              $id
 * @property      int                              $course_module_id
 * @property      string                           $slug
 * @property      int                              $position
 * @property      array<string, string>            $title
 * @property      list<array<string, mixed>>       $steps теория, задачи, проверка знаний — без эталонов
 * @property-read CourseModule                     $module
 * @property-read Carbon|null                      $created_at
 * @property-read Carbon|null                      $updated_at
 */
final class Lesson extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['course_module_id', 'slug', 'position', 'title', 'steps'];

    /**
     * @return BelongsTo<CourseModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['title' => 'array', 'steps' => 'array'];
    }
}
