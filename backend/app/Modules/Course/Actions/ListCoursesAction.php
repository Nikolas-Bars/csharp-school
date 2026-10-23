<?php

declare(strict_types=1);

namespace App\Modules\Course\Actions;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Repositories\CourseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

/** Курсы платформы для главной: по порядку courses.json. Кривой курсор — первая страница */
final readonly class ListCoursesAction
{
    /** Курсов единицы: одна страница вмещает все, курсор — по общему правилу списков */
    public const int PER_PAGE = 50;

    public function __construct(private CourseRepository $courses) {}

    /**
     * @return CursorPaginator<int, Course>
     */
    public function run(?string $cursor): CursorPaginator
    {
        return $this->courses->page(self::PER_PAGE, $cursor);
    }
}
