<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\PasswordResetToken;
use App\Repositories\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Токены сброса пароля. В базе — только SHA-256 токена; сам токен уходит в письме.
 *
 * @extends BaseRepository<PasswordResetToken>
 */
final class PasswordResetTokenRepository extends BaseRepository
{
    /** Новая ссылка заменяет прежнюю — одним запросом, поэтому два запроса одновременно не столкнутся */
    public function replace(string $email, string $tokenHash): void
    {
        $this->query()->upsert(
            [['email' => $email, 'token' => $tokenHash, 'created_at' => Carbon::now()]],
            ['email'],
            ['token', 'created_at'],
        );
    }

    public function findCreatedAfter(string $email, CarbonInterface $after): ?PasswordResetToken
    {
        return $this->query()->whereKey($email)->where('created_at', '>', $after)->first();
    }

    /**
     * @return bool false — ссылки уже нет (её только что использовали)
     */
    public function delete(string $email): bool
    {
        return $this->query()->whereKey($email)->delete() > 0;
    }

    protected function model(): string
    {
        return PasswordResetToken::class;
    }
}
