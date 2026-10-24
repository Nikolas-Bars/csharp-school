<?php

declare(strict_types=1);

namespace App\Modules\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Карточка слова курса (шаг words урока английского). Формат карточки — content/README.md.
 *
 * @property      int                  $id
 * @property      int                  $course_id
 * @property      int                  $lesson_id урок, где слово появилось (null — только посреди импорта)
 * @property      string               $slug      id карточки в content: один на курс
 * @property      int                  $position  порядок в курсе: по модулям, урокам и карточкам
 * @property      array<string, mixed> $card      карточка целиком, тексты по языкам
 * @property-read Course               $course
 * @property-read Lesson               $lesson
 * @property-read Carbon|null          $created_at
 * @property-read Carbon|null          $updated_at
 */
final class Word extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['course_id', 'lesson_id', 'slug', 'position', 'card'];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['card' => 'array'];
    }
}
