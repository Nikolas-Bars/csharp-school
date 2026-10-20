<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use OpenApi\Builder;
use OpenApi\Utils\SourceFinder;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\OpenApi\OpenApiSpec;
use Tests\TestCase;

/**
 * Документация API (openapi.json) не расходится с кодом: файл собран из текущих атрибутов,
 * описан каждый маршрут — с теми же параметрами, входом, лимитами и полями формы. Что ответы
 * совпадают со схемами, проверяют все feature-тесты (Tests\TestCase::createTestResponse).
 */
final class OpenApiTest extends TestCase
{
    private const string REGENERATE = 'обнови документацию: `make composer cmd=openapi`';

    public function testSpecificationFileIsBuiltFromCurrentCode(): void
    {
        // Те же источники, что у `composer openapi`: app без тестов модулей
        $result = (new Builder())->addSource(new SourceFinder(app_path(), 'Tests'))->build();

        self::assertSame([], [...$result->errors(), ...$result->warnings()], 'Генератор OpenAPI ругается на атрибуты');
        self::assertFileExists(OpenApiSpec::FILE);
        self::assertSame($result->toArray(), OpenApiSpec::load()->document, 'openapi.json устарел — ' . self::REGENERATE);
    }

    public function testEveryApiRouteIsDocumentedAndNothingElse(): void
    {
        $routes = array_keys($this->apiRoutes());
        $documented = array_keys(OpenApiSpec::load()->operations());
        sort($routes);
        sort($documented);

        self::assertSame($routes, $documented, 'Маршруты и openapi.json различаются — опиши маршрут атрибутом #[OA\Get]/#[OA\Post] и ' . self::REGENERATE);
    }

    public function testOperationsMatchRouteSettings(): void
    {
        $operations = OpenApiSpec::load()->operations();

        foreach ($this->apiRoutes() as $key => $route) {
            $operation = $operations[$key] ?? [];
            $responses = array_map('strval', array_keys((array) ($operation['responses'] ?? [])));
            $middleware = $route->gatherMiddleware();
            $private = \in_array('auth:sanctum', $middleware, true);

            // Открытый эндпоинт — явно `security: []`
            self::assertSame($private ? [['bearer' => []]] : [], $operation['security'] ?? null, "{$key}: вход по токену (security) — как у маршрута");
            // 401 бывает и без входа по токену (неверный пароль), поэтому только в одну сторону
            self::assertTrue(!$private || \in_array('401', $responses, true), "{$key}: нужен вход — описан ответ 401");
            self::assertSame(
                array_filter($middleware, static fn(mixed $name): bool => \is_string($name) && str_starts_with($name, 'throttle:')) !== [],
                \in_array('429', $responses, true),
                "{$key}: ответ 429 описан тогда и только тогда, когда у маршрута есть throttle",
            );
            self::assertEqualsCanonicalizing($route->parameterNames(), $this->pathParameters($operation), "{$key}: параметры пути");
        }
    }

    public function testRequestBodiesMatchFormRequests(): void
    {
        $spec = OpenApiSpec::load();
        $operations = $spec->operations();

        foreach ($this->apiRoutes() as $key => $route) {
            $operation = $operations[$key] ?? [];
            $request = $this->formRequest($route);
            $body = (array) ($operation['requestBody'] ?? []);

            if ($request === null) {
                self::assertSame([], $body, "{$key}: у маршрута нет FormRequest — тела запроса в документации быть не должно");

                continue;
            }

            /** @var array<string, list<mixed>> $rules */
            $rules = $request->rules();
            $fields = array_values(array_filter(array_keys($rules), static fn(string $field): bool => !str_contains($field, '.')));
            $required = array_values(array_filter($fields, static fn(string $field): bool => \in_array('required', $rules[$field], true)));
            $schema = $this->schemaOf($body);

            self::assertTrue($body['required'] ?? false, "{$key}: тело запроса обязательно");
            self::assertEqualsCanonicalizing($fields, array_keys((array) ($schema['properties'] ?? [])), "{$key}: поля тела запроса — как в rules() " . $request::class);
            self::assertEqualsCanonicalizing($required, (array) ($schema['required'] ?? []), "{$key}: обязательные поля — как в rules() " . $request::class);
            self::assertArrayHasKey('422', (array) ($operation['responses'] ?? []), "{$key}: у формы есть ответ 422");
        }
    }

    /**
     * Маршруты API: «post /api/auth/login» → маршрут (HEAD Laravel добавляет к GET сам).
     *
     * @return array<string, RouteDefinition>
     */
    private function apiRoutes(): array
    {
        $routes = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                if (str_starts_with($route->uri(), 'api/')) {
                    $routes[strtolower($method) . ' /' . $route->uri()] = $route;
                }
            }
        }

        return $routes;
    }

    /**
     * @param array<string, mixed> $operation
     *
     * @return list<string>
     */
    private function pathParameters(array $operation): array
    {
        $spec = OpenApiSpec::load()->document;
        $names = [];

        foreach ((array) ($operation['parameters'] ?? []) as $parameter) {
            $parameter = (array) $parameter;
            if (isset($parameter['$ref'])) {
                $parameter = (array) data_get($spec, str_replace('/', '.', substr((string) $parameter['$ref'], 2)));
            }
            if (($parameter['in'] ?? null) === 'path') {
                $names[] = (string) $parameter['name'];
            }
        }

        return $names;
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    private function schemaOf(array $body): array
    {
        $schema = (array) data_get($body, 'content.application/json.schema', []);
        $ref = $schema['$ref'] ?? null;

        return \is_string($ref) ? (array) data_get(OpenApiSpec::load()->document, str_replace('/', '.', substr($ref, 2))) : $schema;
    }

    private function formRequest(RouteDefinition $route): ?FormRequest
    {
        $controller = $route->getControllerClass();
        if ($controller === null) {
            return null;
        }

        foreach ((new ReflectionMethod($controller, '__invoke'))->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), FormRequest::class)) {
                return new ($type->getName())();
            }
        }

        return null;
    }
}
