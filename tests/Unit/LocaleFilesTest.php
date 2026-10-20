<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Каждый язык переводит всё, что есть в русском, и с теми же подстановками (:attribute, :max).
 * Иначе клиент молча получит русский текст или сырой ключ. Английские сообщения валидации
 * берутся из Laravel — загрузчик переводов отдаёт их вместе с нашими.
 */
final class LocaleFilesTest extends TestCase
{
    public function testEveryLocaleTranslatesAllRussianKeysWithSamePlaceholders(): void
    {
        $groups = glob(lang_path('ru/*.php'));
        self::assertNotEmpty($groups);

        foreach ($groups as $file) {
            $group = basename($file, '.php');
            $base = $this->strings('ru', $group);

            foreach (Config::array('app.supported_locales') as $locale) {
                self::assertIsString($locale);
                $strings = $this->strings($locale, $group);

                self::assertSame([], array_keys(array_diff_key($base, $strings)), "lang/{$locale}/{$group}.php: нет ключей");

                foreach ($base as $key => $text) {
                    self::assertSame(
                        $this->placeholders($text),
                        $this->placeholders($strings[$key]),
                        "lang/{$locale}/{$group}.php: подстановки в {$key}",
                    );
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function strings(string $locale, string $group): array
    {
        $loader = $this->app?->make('translator')->getLoader();
        self::assertInstanceOf(Loader::class, $loader);

        /** @var array<string, string> */
        return Arr::dot($loader->load($locale, $group));
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $text): array
    {
        preg_match_all('/:([a-z_]+)/', $text, $matches);
        $names = array_values(array_unique($matches[1]));
        sort($names);

        return $names;
    }
}
