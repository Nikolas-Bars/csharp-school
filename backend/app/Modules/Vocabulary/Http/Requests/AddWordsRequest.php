<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Requests;

use App\Modules\Vocabulary\DTO\NewWordDTO;
use App\Modules\Vocabulary\Tasks\ScheduleReviewTask;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AddWordsRequest',
    required: ['words'],
    properties: [
        new OA\Property(property: 'words', type: 'array', items: new OA\Items(
            required: ['word'],
            properties: [
                new OA\Property(property: 'word', description: 'Slug карточки слова', type: 'string', pattern: self::SLUG, example: 'wear'),
                new OA\Property(property: 'box', description: 'Только со словарём гостя: коробка повторения', type: 'integer', maximum: ScheduleReviewTask::LAST_BOX, minimum: 0),
                new OA\Property(property: 'due_at', description: 'Только со словарём гостя: когда повторить. Позже, чем позволяет коробка (часы гостя убежали), — срок обрезается', type: 'string', format: 'date-time'),
            ],
            type: 'object',
            additionalProperties: false,
        ), maxItems: self::MAX_WORDS, minItems: 1),
        new OA\Property(property: 'guest', description: 'Словарь гостя после входа: переносит своё расписание, но только для слов, которых в аккаунте нет', type: 'boolean', default: false),
    ],
)]
final class AddWordsRequest extends FormRequest
{
    /** Все слова курса гостя — пара сотен, не больше */
    private const int MAX_WORDS = 500;

    /** Slug карточки слова */
    private const string SLUG = '^[a-z0-9][a-z0-9-]{0,63}$';

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'words'        => ['required', 'array', 'min:1', 'max:' . self::MAX_WORDS],
            'words.*'      => ['required', 'array:word,box,due_at'],
            'words.*.word' => ['required', 'string', 'regex:/' . self::SLUG . '/'],
            // strict: «2» строкой или 2.0 — ошибка ученика (422), а не TypeError дальше
            'words.*.box'    => ['sometimes', 'integer:strict', 'between:0,' . ScheduleReviewTask::LAST_BOX],
            'words.*.due_at' => ['sometimes', 'date'],
            // Словарь гостя после входа: своё расписание — только для слов, которых в аккаунте нет
            'guest' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['words' => Translator::get('fields.vocabulary.words')];
    }

    public function fromGuest(): bool
    {
        return $this->boolean('guest');
    }

    /**
     * @return list<NewWordDTO>
     */
    public function toDTOs(): array
    {
        /** @var list<array{word: string, box?: int, due_at?: string}> $words */
        $words = $this->validated('words');

        return array_map(static fn(array $word): NewWordDTO => new NewWordDTO(
            $word['word'],
            $word['box'] ?? null,
            // В базе время — в UTC: «+03:00» из запроса иначе сдвинул бы срок на три часа
            isset($word['due_at']) ? Carbon::parse($word['due_at'])->utc() : null,
        ), $words);
    }
}
