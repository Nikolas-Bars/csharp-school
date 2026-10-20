<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\OpenApi\SchemaValidator;

/**
 * Сверка ответов с openapi.json ловит расхождения: другой тип, нет поля, лишнее поле, не тот
 * вариант. Иначе проверка ответов в feature-тестах проходила бы всегда.
 */
final class SchemaValidatorTest extends TestCase
{
    private const array DOCUMENT = [
        'components' => [
            'schemas' => [
                'Envelope' => [
                    'type'       => 'object',
                    'required'   => ['status', 'data'],
                    'properties' => ['status' => ['type' => 'string', 'enum' => ['success']], 'data' => []],
                ],
                'User' => [
                    'type'       => 'object',
                    'required'   => ['id', 'name'],
                    'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => ['string', 'null']]],
                ],
            ],
        ],
    ];

    private const array USER_RESPONSE = [
        'allOf' => [
            ['$ref' => '#/components/schemas/Envelope'],
            ['properties' => ['data' => ['$ref' => '#/components/schemas/User']]],
        ],
    ];

    public function testMatchingResponsePasses(): void
    {
        self::assertSame([], $this->validate('{"status": "success", "data": {"id": 1, "name": null}}', self::USER_RESPONSE));
    }

    public function testWrongTypeFails(): void
    {
        self::assertSame(['$.data.id: ожидался integer, а пришло string'], $this->validate('{"status": "success", "data": {"id": "1", "name": "Anya"}}', self::USER_RESPONSE));
    }

    public function testMissingFieldFails(): void
    {
        self::assertSame(['$.data.name: обязательного поля нет'], $this->validate('{"status": "success", "data": {"id": 1}}', self::USER_RESPONSE));
    }

    public function testUndocumentedFieldFails(): void
    {
        self::assertSame(
            ['$.data.email: поля нет в схеме — опиши его в openapi.json'],
            $this->validate('{"status": "success", "data": {"id": 1, "name": "Anya", "email": "a@b.c"}}', self::USER_RESPONSE),
        );
    }

    public function testEnumIsChecked(): void
    {
        self::assertSame(['$.status: "error" нет среди ["success"]'], $this->validate('{"status": "error", "data": null}', ['$ref' => '#/components/schemas/Envelope']));
    }

    public function testEmptyObjectAndEmptyListDiffer(): void
    {
        $map = ['type' => 'object', 'additionalProperties' => ['type' => 'string']];

        self::assertSame([], $this->validate('{}', $map));
        self::assertSame([], $this->validate('{"hero-health": "int hp = 100;"}', $map));
        self::assertSame(['$: ожидался object, а пришло array'], $this->validate('[]', $map));
        self::assertSame(['$.quiz: ожидался string, а пришло int'], $this->validate('{"quiz": 1}', $map));
    }

    public function testListItemsAreChecked(): void
    {
        self::assertSame(['$[1]: ожидался string, а пришло int'], $this->validate('["a", 2]', ['type' => 'array', 'items' => ['type' => 'string']]));
    }

    public function testExactlyOneOneOfVariantMustMatch(): void
    {
        $step = ['oneOf' => [
            ['type' => 'object', 'required' => ['type'], 'properties' => ['type' => ['enum' => ['theory']], 'text' => ['type' => 'string']]],
            ['type' => 'object', 'required' => ['type'], 'properties' => ['type' => ['enum' => ['quiz']]]],
        ]];

        self::assertSame([], $this->validate('{"type": "theory", "text": "Привет"}', $step));
        self::assertCount(1, $this->validate('{"type": "video"}', $step));
        self::assertSame(['$: подходит сразу под 2 варианта oneOf'], $this->validate('{"type": "quiz"}', ['oneOf' => [['type' => 'object'], ['type' => 'object']]]));
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    private function validate(string $json, array $schema): array
    {
        return (new SchemaValidator(self::DOCUMENT))->validate(json_decode($json, false, flags: JSON_THROW_ON_ERROR), $schema);
    }
}
