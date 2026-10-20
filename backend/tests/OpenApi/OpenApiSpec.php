<?php

declare(strict_types=1);

namespace Tests\OpenApi;

use JsonException;

/**
 * Спецификация API из openapi.json (её собирает `composer openapi` из атрибутов в коде).
 * Tests\TestCase сверяет с ней ответ каждого запроса feature-тестов к описанному эндпоинту.
 */
final class OpenApiSpec
{
    public const string FILE = __DIR__ . '/../../openapi.json';

    private const array METHODS = ['get', 'put', 'post', 'delete', 'patch', 'options', 'head', 'trace'];

    /** Файл читается один раз на весь прогон тестов */
    private static ?self $loaded = null;

    private readonly SchemaValidator $validator;

    /**
     * @param array<string, mixed> $document
     */
    private function __construct(public readonly array $document)
    {
        $this->validator = new SchemaValidator($document);
    }

    public static function load(): self
    {
        if (self::$loaded === null) {
            $json = file_get_contents(self::FILE);
            /** @var array<string, mixed> $document */
            $document = json_decode($json === false ? '' : $json, true, flags: JSON_THROW_ON_ERROR);
            self::$loaded = new self($document);
        }

        return self::$loaded;
    }

    /**
     * Описанные операции: «get /api/courses/{course}» → операция.
     *
     * @return array<string, array<string, mixed>>
     */
    public function operations(): array
    {
        $operations = [];
        foreach ($this->map($this->document['paths'] ?? []) as $path => $item) {
            foreach (array_intersect_key($this->map($item), array_flip(self::METHODS)) as $method => $operation) {
                $operations["{$method} {$path}"] = $this->map($operation);
            }
        }

        return $operations;
    }

    /**
     * Ответ запроса — такой, как описан в спецификации?
     *
     * @return list<string> ошибки; пусто — совпадает или эндпоинт не описан (тестовые маршруты)
     */
    public function check(string $method, string $uri, int $status, string $body): array
    {
        $operation = $this->find($method, $uri);
        if ($operation === null) {
            return [];
        }

        [$key, $responses] = [$operation[0], $this->map($operation[1]['responses'] ?? [])];
        if (!isset($responses[(string) $status])) {
            return ["{$key}: ответ {$status} не описан"];
        }

        $response = $this->map($responses[(string) $status]);
        if (isset($response['$ref']) && \is_string($response['$ref'])) {
            $response = $this->validator->resolve($response['$ref']);
        }

        $schema = $this->map($this->map($this->map($response['content'] ?? [])['application/json'] ?? [])['schema'] ?? []);
        if ($schema === []) {
            return $body === '' ? [] : ["{$key}: у ответа {$status} нет тела в спецификации, а пришло: {$body}"];
        }

        try {
            $data = json_decode($body, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ["{$key}: ответ {$status} — не JSON: {$body}"];
        }

        return array_map(static fn(string $error): string => "{$key} → {$status}: {$error}", $this->validator->validate($data, $schema));
    }

    /**
     * Операция по методу и адресу запроса: шаблон «/api/courses/{course}» подходит к «/api/courses/csharp».
     *
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private function find(string $method, string $uri): ?array
    {
        foreach ($this->operations() as $key => $operation) {
            [$operationMethod, $path] = explode(' ', $key, 2);
            $pattern = '#^' . preg_replace('#\\\\\{[^/]+?\\\\\}#', '[^/]+', preg_quote($path, '#')) . '$#';

            if ($operationMethod === strtolower($method) && preg_match($pattern, $uri) === 1) {
                return [$key, $operation];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function map(mixed $value): array
    {
        /** @var array<string, mixed> */
        return \is_array($value) ? $value : [];
    }
}
