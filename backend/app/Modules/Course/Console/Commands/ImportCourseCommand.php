<?php

declare(strict_types=1);

namespace App\Modules\Course\Console\Commands;

use App\Modules\Course\Actions\ImportCourseAction;
use App\Modules\Course\Exceptions\InvalidContentException;
use App\Services\Translator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/** make artisan cmd='course:import' — загрузить content/ в базу (после правки уроков) */
final class ImportCourseCommand extends Command
{
    /** @var string */
    protected $signature = 'course:import {path? : папка контента, по умолчанию config course.content_path}';

    /** @var string */
    protected $description = 'Загрузить учебный курс из content/ в базу';

    public function handle(ImportCourseAction $import): int
    {
        $path = $this->argument('path');

        try {
            $course = $import->run(\is_string($path) ? $path : Config::string('course.content_path'));
        } catch (InvalidContentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(Translator::get('messages.course.imported', [
            'course'  => $course->slug,
            'modules' => \count($course->modules),
            'lessons' => array_sum(array_map(static fn($module): int => \count($module->lessons), $course->modules)),
        ]));

        return self::SUCCESS;
    }
}
