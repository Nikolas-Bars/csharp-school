<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * Текст на языке запроса из набора {"ru": "…", "en": "…"} (учебный контент). Перевода нет —
 * язык по умолчанию (первый из app.supported_locales), затем любой имеющийся.
 */
final class Localized
{
    /**
     * @param array<array-key, mixed> $texts
     */
    public static function pick(array $texts): string
    {
        /** @var list<string> $supported */
        $supported = Config::array('app.supported_locales');

        foreach ([App::getLocale(), $supported[0] ?? 'ru'] as $locale) {
            if (\is_string($texts[$locale] ?? null)) {
                return $texts[$locale];
            }
        }

        $first = reset($texts);

        return \is_string($first) ? $first : '';
    }
}
