<?php

declare(strict_types=1);

namespace App\Modules\Course\Tasks;

use App\Modules\Course\Contracts\LessonStepsDTO;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Repositories\CourseRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Уроки курса и их засчитываемые шаги — на каждый запрос прогресса. Курс меняется только
 * импортом, поэтому список лежит в кэше, пока course:import его не сбросит (ForgetLessonStepsTask):
 * шаги уроков (теория, вопросы, проверки) из базы на каждый запрос не читаются.
 */
final readonly class ListLessonStepsTask
{
    public const string CACHE_KEY = 'course:%s:lesson-steps';

    public function __construct(private CourseRepository $courses) {}

    /**
     * @return list<LessonStepsDTO> по порядку модулей и уроков; курса нет — пустой список
     */
    public function run(string $course): array
    {
        /** @var list<array{id: int, module: string, lesson: string, steps: list<string>}> $lessons */
        $lessons = Cache::rememberForever(\sprintf(self::CACHE_KEY, $course), fn(): array => $this->read($course));

        return array_map(
            static fn(array $lesson): LessonStepsDTO => new LessonStepsDTO($lesson['id'], $lesson['module'], $lesson['lesson'], $lesson['steps']),
            $lessons,
        );
    }

    /**
     * В кэше — массивы, а не объекты: кэш не зависит от классов PHP.
     *
     * @return list<array{id: int, module: string, lesson: string, steps: list<string>}>
     */
    private function read(string $course): array
    {
        $found = $this->courses->findWithStructure($course);
        $lessons = [];

        // Всё уже загружено findWithStructure — в цикле нет запросов
        foreach ($found === null ? [] : $found->modules as $module) {
            foreach ($module->lessons as $lesson) {
                /** @var Lesson $lesson */
                $lessons[] = ['id' => $lesson->id, 'module' => $module->slug, 'lesson' => $lesson->slug, 'steps' => $lesson->countedSteps()];
            }
        }

        return $lessons;
    }
}
