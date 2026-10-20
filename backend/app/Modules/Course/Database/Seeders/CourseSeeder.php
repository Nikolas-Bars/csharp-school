<?php

declare(strict_types=1);

namespace App\Modules\Course\Database\Seeders;

use App\Modules\Course\Actions\ImportCourseAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

/** Курс из content/ — тот же импорт, что и course:import */
final class CourseSeeder extends Seeder
{
    public function run(ImportCourseAction $import): void
    {
        $import->run(Config::string('course.content_path'));
    }
}
