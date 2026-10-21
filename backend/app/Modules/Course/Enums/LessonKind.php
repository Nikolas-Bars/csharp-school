<?php

declare(strict_types=1);

namespace App\Modules\Course\Enums;

/**
 * Вид урока: обычный или контрольная модуля (`"kind": "test"` в lesson.json). Модуль
 * заканчивается контрольной и считается пройденным только после неё (rules/course-content.md).
 */
enum LessonKind: string
{
    case Lesson = 'lesson';
    case Test = 'test';
}
