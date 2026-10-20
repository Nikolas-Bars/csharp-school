<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\OpenApi\OpenApiSpec;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Тексты ответов сверяются с русскими — языком по умолчанию
        $this->withHeader('Accept-Language', 'ru');
    }

    /**
     * Каждый ответ описанного эндпоинта сверяется с openapi.json: код ответа описан, поля и типы
     * совпадают, лишних полей нет. Так документация не отстаёт от кода.
     *
     * @param Response $response
     * @param Request  $request
     *
     * @return TestResponse<Response>
     */
    protected function createTestResponse($response, $request)
    {
        $content = $response->getContent();
        $errors = OpenApiSpec::load()->check($request->getMethod(), $request->getPathInfo(), $response->getStatusCode(), $content === false ? '' : $content);

        self::assertSame([], $errors, 'Ответ не совпадает с openapi.json: поправь атрибуты OpenAPI и выполни `make composer cmd=openapi`');

        return parent::createTestResponse($response, $request);
    }
}
