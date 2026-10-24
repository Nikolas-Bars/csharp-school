<?php

declare(strict_types=1);

namespace App\Modules\Course\Providers;

use App\Modules\Course\Console\Commands\ImportCourseCommand;
use App\Modules\Course\Contracts\LessonDirectory;
use App\Modules\Course\Contracts\WordDirectory;
use App\Modules\Course\Services\LessonDirectoryService;
use App\Modules\Course\Services\WordDirectoryService;
use App\Providers\ModuleServiceProvider;

final class CourseServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LessonDirectory::class, LessonDirectoryService::class);
        $this->app->bind(WordDirectory::class, WordDirectoryService::class);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([ImportCourseCommand::class]);
        }
    }
}
