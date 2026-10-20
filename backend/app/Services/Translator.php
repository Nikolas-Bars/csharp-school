<?php

declare(strict_types=1);

namespace App\Services;

/**
 * __() для кода со строгими типами: всегда возвращает строку. Ключа нет ни в одном языке —
 * вернётся сам ключ, и пропущенный перевод сразу виден в ответе и в тесте.
 */
final class Translator
{
    /**
     * @param array<string, int|float|string> $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $text = __($key, $replace);

        return \is_string($text) ? $text : $key;
    }
}
