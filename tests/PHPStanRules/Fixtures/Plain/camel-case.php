<?php

declare(strict_types=1);

function lessonTitle(int $lesson_id): string
{
    $lessonTitle = 'Урок ' . $lesson_id;
    $HP = 100;

    return $lessonTitle . $HP . $_SERVER['APP_NAME'];
}
