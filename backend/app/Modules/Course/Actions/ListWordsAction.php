<?php

declare(strict_types=1);

namespace App\Modules\Course\Actions;

use App\Modules\Course\Exceptions\CourseNotFoundException;
use App\Modules\Course\Models\Word;
use App\Modules\Course\Repositories\CourseRepository;
use App\Modules\Course\Repositories\WordRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

/** Карточки слов курса по порядку уроков — для словаря (и гостя, и вошедшего). Кривой курсор — первая страница */
final readonly class ListWordsAction
{
    /** В курсе английского около двухсот слов: две-три страницы */
    public const int PER_PAGE = 100;

    public function __construct(
        private CourseRepository $courses,
        private WordRepository $words,
    ) {}

    /**
     * @return CursorPaginator<int, Word>
     */
    public function run(string $course, ?string $cursor): CursorPaginator
    {
        $found = $this->courses->findBySlug($course) ?? throw new CourseNotFoundException();

        return $this->words->page($found->id, self::PER_PAGE, $cursor);
    }
}
