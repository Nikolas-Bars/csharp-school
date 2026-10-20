<?php

declare(strict_types=1);

namespace App\Modules\Course\Contracts;

/**
 * Уроки курса для других модулей (Progress): какие в уроке шаги засчитываются. Модели Course
 * наружу не выходят.
 */
interface LessonDirectory
{
    /**
     * Все уроки курса по порядку; курса нет — пустой список.
     *
     * @return list<LessonStepsDTO>
     */
    public function lessonsOf(string $course): array;
}
