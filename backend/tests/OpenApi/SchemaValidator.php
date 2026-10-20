<?php

declare(strict_types=1);

namespace Tests\OpenApi;

use LogicException;
use stdClass;

/**
 * Проверка JSON по схеме из openapi.json — только то, что в спецификации используется: $ref,
 * allOf, oneOf, type, enum, required, properties, additionalProperties, items.
 *
 * Строже JSON Schema в одном: у объекта со списком properties лишних полей быть не должно, даже
 * без additionalProperties: false. Новое поле ответа, которого нет в документации, — ошибка теста.
 * Значение — как из json_decode($json, false): объект — stdClass, список — массив.
 */
final readonly class SchemaValidator
{
    /**
     * @param array<string, mixed> $document вся спецификация: для $ref
     */
    public function __construct(private array $document) {}

    /**
     * @param array<string, mixed> $schema
     *
     * @return list<string> ошибки: путь в JSON и что не так; пусто — подходит
     */
    public function validate(mixed $value, array $schema, string $path = '$'): array
    {
        $schema = $this->flatten($schema);

        if (isset($schema['oneOf'])) {
            return $this->validateOneOf($value, self::schemas($schema['oneOf']), $path);
        }

        if (isset($schema['type']) && !self::hasType($value, (array) $schema['type'])) {
            return [\sprintf('%s: ожидался %s, а пришло %s', $path, implode('|', (array) $schema['type']), get_debug_type($value))];
        }

        if (isset($schema['enum']) && \is_array($schema['enum']) && !\in_array($value, $schema['enum'], true)) {
            return [\sprintf('%s: %s нет среди %s', $path, json_encode($value, JSON_UNESCAPED_UNICODE), json_encode($schema['enum'], JSON_UNESCAPED_UNICODE))];
        }

        if ($value instanceof stdClass) {
            return $this->validateObject($value, $schema, $path);
        }

        if (\is_array($value) && isset($schema['items']) && \is_array($schema['items'])) {
            $errors = [];
            foreach ($value as $index => $item) {
                $errors = [...$errors, ...$this->validate($item, self::schema($schema['items']), "{$path}[{$index}]")];
            }

            return $errors;
        }

        return [];
    }

    /**
     * Схема по ссылке «#/components/…».
     *
     * @return array<string, mixed>
     */
    public function resolve(string $ref): array
    {
        $node = $this->document;
        foreach (explode('/', substr($ref, 2)) as $key) {
            $node = \is_array($node) ? ($node[$key] ?? null) : null;
        }

        return \is_array($node) ? self::schema($node) : throw new LogicException("В openapi.json нет {$ref}");
    }

    /**
     * $ref → сама схема, allOf → одна схема: поля и required всех частей (поле из поздней части
     * заменяет раннее — так `data` конкретного ответа уточняет `data` общей обёртки).
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function flatten(array $schema): array
    {
        if (isset($schema['$ref']) && \is_string($schema['$ref'])) {
            return $this->flatten($this->resolve($schema['$ref']));
        }

        if (!isset($schema['allOf'])) {
            return $schema;
        }

        $merged = $schema;
        unset($merged['allOf']);
        foreach (self::schemas($schema['allOf']) as $part) {
            $part = $this->flatten($part);
            $merged['properties'] = [...self::schema($merged['properties'] ?? []), ...self::schema($part['properties'] ?? [])];
            $merged['required'] = array_values(array_unique([...(array) ($merged['required'] ?? []), ...(array) ($part['required'] ?? [])]));
            $merged += $part;
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    private function validateObject(stdClass $value, array $schema, string $path): array
    {
        $fields = get_object_vars($value);
        $properties = self::schema($schema['properties'] ?? []);
        $errors = [];

        foreach ((array) ($schema['required'] ?? []) as $name) {
            if (!\array_key_exists((string) $name, $fields)) {
                $errors[] = "{$path}.{$name}: обязательного поля нет";
            }
        }

        $additional = $schema['additionalProperties'] ?? ($properties === [] ? true : false);
        foreach ($fields as $name => $field) {
            $fieldSchema = $properties[$name] ?? (\is_array($additional) ? $additional : null);

            if ($fieldSchema === null && $additional === false) {
                $errors[] = "{$path}.{$name}: поля нет в схеме — опиши его в openapi.json";
            } elseif (\is_array($fieldSchema)) {
                $errors = [...$errors, ...$this->validate($field, self::schema($fieldSchema), "{$path}.{$name}")];
            }
        }

        return $errors;
    }

    /**
     * @param list<array<string, mixed>> $variants
     *
     * @return list<string>
     */
    private function validateOneOf(mixed $value, array $variants, string $path): array
    {
        $failures = array_map(fn(array $variant): array => $this->validate($value, $variant, $path), $variants);
        $matched = \count(array_filter($failures, static fn(array $errors): bool => $errors === []));

        return match ($matched) {
            1       => [],
            0       => ["{$path}: не подходит ни под один вариант oneOf (" . implode('; ', array_merge(...$failures)) . ')'],
            default => ["{$path}: подходит сразу под {$matched} варианта oneOf"],
        };
    }

    /**
     * @param list<mixed> $types
     */
    private static function hasType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            $matches = match ($type) {
                'string'  => \is_string($value),
                'integer' => \is_int($value),
                'number'  => \is_int($value) || \is_float($value),
                'boolean' => \is_bool($value),
                'null'    => $value === null,
                'array'   => \is_array($value) && array_is_list($value),
                'object'  => $value instanceof stdClass,
                default   => false,
            };

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(mixed $value): array
    {
        /** @var array<string, mixed> */
        return \is_array($value) ? $value : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function schemas(mixed $value): array
    {
        return array_values(array_map(self::schema(...), \is_array($value) ? $value : []));
    }
}
