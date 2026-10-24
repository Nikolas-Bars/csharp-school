<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Actions;

use App\Modules\Course\Contracts\WordDirectory;
use App\Modules\Vocabulary\DTO\LearnedWordDTO;
use App\Modules\Vocabulary\DTO\NewWordDTO;
use App\Modules\Vocabulary\Tasks\AddWordsTask;
use App\Modules\Vocabulary\Tasks\BuildVocabularyTask;
use Carbon\CarbonImmutable;

/**
 * Слова урока — в словарь (ученик прошёл карточки или урок) или словарь гостя пачкой после входа.
 * Слово добавляется один раз; неизвестные пропускаются.
 */
final readonly class AddWordsAction
{
    public function __construct(
        private WordDirectory $words,
        private AddWordsTask $addWords,
        private BuildVocabularyTask $buildVocabulary,
    ) {}

    /**
     * @param list<NewWordDTO> $added
     *
     * @return list<LearnedWordDTO> словарь по курсу после записи
     */
    public function run(int $userId, string $course, array $added, bool $fromGuest = false): array
    {
        $words = $this->words->wordsOf($course);
        $this->addWords->run($userId, $words, $added, $fromGuest, CarbonImmutable::now());

        return $this->buildVocabulary->run($userId, $words);
    }
}
