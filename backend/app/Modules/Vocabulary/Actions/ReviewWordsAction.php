<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Actions;

use App\Modules\Course\Contracts\WordDirectory;
use App\Modules\Vocabulary\DTO\LearnedWordDTO;
use App\Modules\Vocabulary\DTO\ReviewDTO;
use App\Modules\Vocabulary\Tasks\ApplyReviewsTask;
use App\Modules\Vocabulary\Tasks\BuildVocabularyTask;
use Carbon\CarbonImmutable;

/** Итоги повторения: «Помню» отодвигает слово, «Не помню» — возвращает на завтра */
final readonly class ReviewWordsAction
{
    public function __construct(
        private WordDirectory $words,
        private ApplyReviewsTask $applyReviews,
        private BuildVocabularyTask $buildVocabulary,
    ) {}

    /**
     * @param list<ReviewDTO> $reviews
     *
     * @return list<LearnedWordDTO> словарь по курсу после записи
     */
    public function run(int $userId, string $course, array $reviews): array
    {
        $words = $this->words->wordsOf($course);
        $this->applyReviews->run($userId, $words, $reviews, CarbonImmutable::now());

        return $this->buildVocabulary->run($userId, $words);
    }
}
