<?php

declare(strict_types=1);

namespace App\Modules\Course\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                          $id
 * @property      string                       $slug
 * @property      int                          $position    порядок на главной (courses.json)
 * @property      array<string, string>        $title       по языкам
 * @property      array<string, string>|null   $description по языкам; null — курс ещё не импортировали заново
 * @property-read Collection<int, CourseModule> $modules
 * @property-read Carbon|null                  $created_at
 * @property-read Carbon|null                  $updated_at
 */
final class Course extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['slug', 'position', 'title', 'description'];

    /**
     * @return HasMany<CourseModule, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array'];
    }
}
