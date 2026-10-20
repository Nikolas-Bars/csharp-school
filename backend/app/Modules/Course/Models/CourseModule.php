<?php

declare(strict_types=1);

namespace App\Modules\Course\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Модуль курса («Первые программы»). Не путать с модулями бэкенда.
 *
 * @property      int                     $id
 * @property      int                     $course_id
 * @property      string                  $slug
 * @property      int                     $position
 * @property      array<string, string>   $title
 * @property      array<string, string>   $description
 * @property-read Course                  $course
 * @property-read Collection<int, Lesson> $lessons
 * @property-read Carbon|null             $created_at
 * @property-read Carbon|null             $updated_at
 */
final class CourseModule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['course_id', 'slug', 'position', 'title', 'description'];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array'];
    }
}
