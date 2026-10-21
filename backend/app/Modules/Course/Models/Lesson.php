<?php

declare(strict_types=1);

namespace App\Modules\Course\Models;

use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Course\Enums\LessonKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                              $id
 * @property      int                              $course_module_id
 * @property      string                           $slug
 * @property      int                              $position
 * @property      LessonKind                       $kind     обычный урок или контрольная модуля
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
    protected $fillable = ['course_module_id', 'slug', 'position', 'kind', 'title', 'steps'];

    /**
     * @return BelongsTo<CourseModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    /**
     * Шаги, которые засчитываются в прогресс, по порядку: id задач и «quiz». Одно место и для
     * карты курса (гость), и для Progress (вошедший) — «пройден» у них не разойдётся.
     *
     * @return list<string>
     */
    public function countedSteps(): array
    {
        $keys = [];

        foreach ($this->steps as $step) {
            if ($step['type'] === 'task' && \is_string($step['id'] ?? null)) {
                $keys[] = $step['id'];
            } elseif ($step['type'] === 'quiz') {
                $keys[] = LessonStepsDTO::QUIZ;
            }
        }

        return $keys;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => LessonKind::class, 'title' => 'array', 'steps' => 'array'];
    }
}
