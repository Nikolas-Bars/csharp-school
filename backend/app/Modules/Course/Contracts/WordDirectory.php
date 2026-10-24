<?php

declare(strict_types=1);

namespace App\Modules\Course\Contracts;

/**
 * Карточки слов курса для других модулей (Vocabulary): только id и slug — словарь ученика хранит
 * id карточки, а ученик и фронт называют слово его slug. Модели Course наружу не выходят.
 */
interface WordDirectory
{
    /**
     * Все слова курса; курса нет или в нём нет карточек — пустой массив.
     *
     * @return array<int, string> id карточки → slug
     */
    public function wordsOf(string $course): array;
}
