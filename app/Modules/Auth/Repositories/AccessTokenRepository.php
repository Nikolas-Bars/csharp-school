<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Токены входа Sanctum. В базе — только SHA-256 токена; открытый текст видит один раз клиент.
 *
 * @extends BaseRepository<PersonalAccessToken>
 */
final class AccessTokenRepository extends BaseRepository
{
    /** Как пользователь записан в чужих таблицах: morph map модуля User */
    private const string USER_TYPE = 'user';

    /**
     * @return string токен в формате Sanctum «{id}|{секрет}» — его клиент шлёт в Authorization: Bearer
     */
    public function create(int $userId, string $name): string
    {
        $secret = Str::random(40);

        // forceCreate: у модели Sanctum поля владельца не в $fillable, а данные здесь — не ввод пользователя
        $token = $this->query()->forceCreate([
            'tokenable_type' => self::USER_TYPE,
            'tokenable_id'   => $userId,
            'name'           => $name,
            'token'          => hash('sha256', $secret),
            'abilities'      => ['*'],
        ]);

        return "{$token->id}|{$secret}";
    }

    /** Удаляет токен, только если он принадлежит этому пользователю */
    public function delete(int $tokenId, int $userId): void
    {
        $this->query()
            ->whereKey($tokenId)
            ->where('tokenable_type', self::USER_TYPE)
            ->where('tokenable_id', $userId)
            ->delete();
    }

    protected function model(): string
    {
        return PersonalAccessToken::class;
    }
}
