<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use App\Modules\Course\Repositories\WordRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Слова курса (id → slug) — на каждый запрос словаря. Курс меняется только импортом, поэтому
 * список лежит в кэше, пока course:import его не сбросит (ForgetLessonStepsTask). Пустой не кэшируется:
 * иначе любой slug в адресе оставлял бы в Redis вечный ключ.
 */
final readonly class ListWordSlugsTask
{
    public const string CACHE_KEY = 'course:%s:words';

    public function __construct(private WordRepository $words) {}

    /**
     * @return array<int, string>
     */
    public function run(string $course): array
    {
        $key = \sprintf(self::CACHE_KEY, $course);
        /** @var array<int, string>|null $cached */
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        $words = $this->words->slugsOf($course);
        if ($words !== []) {
            Cache::forever($key, $words);
        }

        return $words;
    }
}
