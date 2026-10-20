<?php

declare(strict_types=1);

namespace App\Modules\Progress\DTO;

use Spatie\LaravelData\Data;

/** Браузер сообщает: шаг урока пройден (для задачи — с кодом, который прошёл проверки) */
final class StepCompletionDTO extends Data
{
    public function __construct(
        public string $module,
        public string $lesson,
        public string $step,
        public ?string $code,
    ) {}
}
