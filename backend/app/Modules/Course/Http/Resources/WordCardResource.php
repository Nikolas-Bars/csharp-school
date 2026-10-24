<?php

declare(strict_types=1);

namespace App\Modules\Course\Http\Resources;

use App\Services\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Карточка слова на языке запроса: перевод и объяснения — на языке ученика, английское — как есть.
 * Формат в content — content/README.md, фронт: WordCard в src/types/api.ts.
 *
 * @property array<array-key, mixed> $resource карточка из content
 */
#[OA\Schema(
    schema: 'WordCard',
    description: 'Карточка слова: тексты для ученика — на языке запроса',
    required: ['id', 'word', 'ipa', 'pos', 'translation', 'forms', 'examples', 'compare'],
    properties: [
        new OA\Property(property: 'id', description: 'Slug слова — один на курс', type: 'string', example: 'wear'),
        new OA\Property(property: 'word', type: 'string', example: 'wear'),
        new OA\Property(property: 'ipa', description: 'Американская транскрипция', type: 'string', example: '/wɛr/'),
        new OA\Property(property: 'pos', description: 'Часть речи', type: 'string', enum: self::PARTS_OF_SPEECH, example: 'verb'),
        new OA\Property(property: 'translation', type: 'string', example: 'носить (одежду), быть одетым в…'),
        new OA\Property(property: 'forms', description: 'Три формы неправильного глагола; у остальных слов — null', oneOf: [
            new OA\Schema(type: 'array', items: new OA\Items(type: 'string'), example: ['wear', 'wore', 'worn']),
            new OA\Schema(type: 'null'),
        ]),
        new OA\Property(property: 'examples', type: 'array', items: new OA\Items(
            required: ['en', 'translation', 'why'],
            properties: [
                new OA\Property(property: 'en', type: 'string', example: 'She is wearing a red jacket today.'),
                new OA\Property(property: 'translation', type: 'string', example: 'Сегодня на ней красная куртка.'),
                new OA\Property(property: 'why', description: 'Почему так: время, значение слова здесь', type: 'string'),
            ],
            type: 'object',
        )),
        new OA\Property(property: 'compare', description: '«Не путай с…»: **жирный** и ==маркер== как в теории', type: ['string', 'null']),
    ],
)]
final class WordCardResource extends JsonResource
{
    public const array PARTS_OF_SPEECH = ['noun', 'verb', 'adjective', 'adverb', 'pronoun', 'preposition', 'conjunction', 'determiner', 'auxiliary', 'phrase'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::card($this->resource);
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(mixed $card): array
    {
        $forms = self::field($card, 'forms');
        $compare = self::field($card, 'compare');
        $examples = self::field($card, 'examples');

        return [
            'id'          => self::field($card, 'id'),
            'word'        => self::field($card, 'word'),
            'ipa'         => self::field($card, 'ipa'),
            'pos'         => self::field($card, 'pos'),
            'translation' => self::text(self::field($card, 'translation')),
            'forms'       => \is_array($forms) ? array_values($forms) : null,
            'examples'    => array_map(static fn(mixed $example): array => [
                'en'          => self::field($example, 'en'),
                'translation' => self::text(self::field($example, 'translation')),
                'why'         => self::text(self::field($example, 'why')),
            ], \is_array($examples) ? array_values($examples) : []),
            'compare' => \is_array($compare) ? self::text($compare) : null,
        ];
    }

    private static function text(mixed $texts): string
    {
        return \is_array($texts) ? Localized::pick($texts) : '';
    }

    private static function field(mixed $data, string $key): mixed
    {
        return \is_array($data) ? ($data[$key] ?? null) : null;
    }
}
