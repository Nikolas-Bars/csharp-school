<?php

declare(strict_types=1);

namespace App\Modules\Course\Services;

use App\Modules\Course\Contracts\WordDirectory;
use App\Modules\Course\Tasks\ListWordSlugsTask;

final readonly class WordDirectoryService implements WordDirectory
{
    public function __construct(private ListWordSlugsTask $listWords) {}

    public function wordsOf(string $course): array
    {
        return $this->listWords->run($course);
    }
}
