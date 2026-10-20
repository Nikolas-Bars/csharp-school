<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Controllers;

use App\Modules\Course\Actions\ShowLessonAction;
use App\Modules\Course\Http\Resources\LessonResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class ShowLessonController
{
    public function __construct(private ShowLessonAction $showLesson) {}

    #[Get('courses/{course}/modules/{module}/lessons/{lesson}')]
    #[OA\Get(
        path: '/api/courses/{course}/modules/{module}/lessons/{lesson}',
        operationId: 'showLesson',
        description: 'Шаги урока по порядку: теория (Markdown), задачи (проверки выполняет runner в браузере), '
            . 'проверка знаний. Эталонные и неверные решения сюда не попадают. Открыто всем, вход не нужен.',
        summary: 'Урок',
        security: [],
        tags: ['Course'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Course'),
            new OA\Parameter(ref: '#/components/parameters/Module'),
            new OA\Parameter(ref: '#/components/parameters/Lesson'),
            new OA\Parameter(ref: '#/components/parameters/AcceptLanguage'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Урок на языке запроса', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Lesson')]),
            ])),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function __invoke(string $course, string $module, string $lesson): JsonResponse
    {
        return ApiResponder::ok(
            LessonResource::make($this->showLesson->run($course, $module, $lesson)),
            Translator::get('messages.course.lesson'),
        );
    }
}
