<?php

declare(strict_types=1);

namespace App\Modules\Course\Providers;

use App\Modules\Course\Console\Commands\ImportCourseCommand;
use App\Providers\ModuleServiceProvider;

final class CourseServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([ImportCourseCommand::class]);
        }
    }
}
