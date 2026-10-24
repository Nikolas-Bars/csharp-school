<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Actions;

use App\Modules\Course\Contracts\WordDirectory;
use App\Modules\Vocabulary\DTO\LearnedWordDTO;
use App\Modules\Vocabulary\Tasks\BuildVocabularyTask;

/** Словарь ученика по курсу: выученные слова и когда их повторять */
final readonly class ShowVocabularyAction
{
    public function __construct(
        private WordDirectory $words,
        private BuildVocabularyTask $buildVocabulary,
    ) {}

    /**
     * @return list<LearnedWordDTO>
     */
    public function run(int $userId, string $course): array
    {
        return $this->buildVocabulary->run($userId, $this->words->wordsOf($course));
    }
}
