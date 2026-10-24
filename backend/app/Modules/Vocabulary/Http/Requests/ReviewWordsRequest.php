<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Requests;

use App\Modules\Vocabulary\DTO\ReviewDTO;
use App\Services\Translator;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ReviewWordsRequest',
    required: ['reviews'],
    properties: [
        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(
            required: ['word', 'remembered'],
            properties: [
                new OA\Property(property: 'word', description: 'Slug карточки слова', type: 'string', pattern: self::SLUG, example: 'wear'),
                new OA\Property(property: 'remembered', description: '«Помню» — true, «Не помню» — false', type: 'boolean'),
            ],
            type: 'object',
            additionalProperties: false,
        ), maxItems: self::MAX_REVIEWS, minItems: 1),
    ],
)]
final class ReviewWordsRequest extends FormRequest
{
    /** Повторение — слова, которые ждут сегодня; в запасе — весь словарь курса */
    private const int MAX_REVIEWS = 500;

    private const string SLUG = '^[a-z0-9][a-z0-9-]{0,63}$';

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reviews'              => ['required', 'array', 'min:1', 'max:' . self::MAX_REVIEWS],
            'reviews.*'            => ['required', 'array:word,remembered'],
            'reviews.*.word'       => ['required', 'string', 'regex:/' . self::SLUG . '/'],
            'reviews.*.remembered' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reviews' => Translator::get('fields.vocabulary.reviews')];
    }

    /**
     * @return list<ReviewDTO>
     */
    public function toDTOs(): array
    {
        /** @var list<array{word: string, remembered: bool|int|string}> $reviews */
        $reviews = $this->validated('reviews');

        return array_map(
            static fn(array $review): ReviewDTO => new ReviewDTO($review['word'], filter_var($review['remembered'], FILTER_VALIDATE_BOOLEAN)),
            $reviews,
        );
    }
}
