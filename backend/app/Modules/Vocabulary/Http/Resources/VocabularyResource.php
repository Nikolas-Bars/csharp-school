<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Resources;

use App\Modules\Vocabulary\DTO\LearnedWordDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Словарь ученика по курсу: slug слов и расписание. Карточки — GET api/courses/{course}/words.
 * Фронт: Vocabulary в src/types/api.ts.
 *
 * @property list<LearnedWordDTO> $resource
 */
#[OA\Schema(
    schema: 'Vocabulary',
    description: 'Выученные слова курса и когда их повторять',
    required: ['words'],
    properties: [
        new OA\Property(property: 'words', type: 'array', items: new OA\Items(
            required: ['word', 'box', 'due_at', 'reviewed_at'],
            properties: [
                new OA\Property(property: 'word', description: 'Slug карточки слова', type: 'string', example: 'wear'),
                new OA\Property(property: 'box', description: 'Коробка повторения: 0 — новое или забытое, 4 — выучено', type: 'integer', example: 1),
                new OA\Property(property: 'due_at', description: 'Когда повторить', type: 'string', format: 'date-time'),
                new OA\Property(property: 'reviewed_at', description: 'Последнее повторение', type: ['string', 'null'], format: 'date-time'),
            ],
            type: 'object',
        )),
    ],
)]
final class VocabularyResource extends JsonResource
{
    /**
     * @return array{words: list<array{word: string, box: int, due_at: string, reviewed_at: string|null}>}
     */
    public function toArray(Request $request): array
    {
        return [
            'words' => array_map(static fn(LearnedWordDTO $word): array => [
                'word'        => $word->word,
                'box'         => $word->box,
                'due_at'      => $word->dueAt->toIso8601String(),
                'reviewed_at' => $word->reviewedAt?->toIso8601String(),
            ], $this->resource),
        ];
    }
}
