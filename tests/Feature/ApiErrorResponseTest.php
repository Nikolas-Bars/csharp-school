<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Любая ошибка API — в общем формате { status, message, data, errors } и на языке запроса,
 * без английских текстов фреймворка и без трассировки.
 */
final class ApiErrorResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/test')->group(static function (): void {
            Route::get('locked', static fn() => throw new LessonLockedException());
            Route::get('crash', static fn() => throw new RuntimeException('секретные подробности'));
            Route::get('throttled', static fn() => 'ok')->middleware('throttle:1,1');
            Route::get('private', static fn() => 'ok')->middleware('auth:sanctum');
            Route::post('validated', static fn(Request $request) => $request->validate(['email' => ['required', 'email']]));
        });
    }

    public function testUnknownAddressIs404(): void
    {
        $this->getJson('/api/nope')->assertNotFound()->assertExactJson($this->error('Такого адреса или записи нет.'));
    }

    public function testWrongMethodIs405InRussian(): void
    {
        $this->deleteJson('/api/test/locked')->assertStatus(405)->assertExactJson($this->error('Этот адрес не принимает такой запрос.'));
    }

    public function testApiExceptionUsesItsTranslationAndStatus(): void
    {
        $this->getJson('/api/test/locked')->assertForbidden()->assertExactJson($this->error('Сюда нельзя: не хватает прав.'));
    }

    public function testValidationListsFieldErrors(): void
    {
        $this->postJson('/api/test/validated', ['email' => 'не почта'])
            ->assertUnprocessable()
            ->assertExactJson($this->error('Проверь, правильно ли заполнены поля.', [
                'email' => ['В поле «email» нужен адрес почты, например anya@example.com.'],
            ]));
    }

    public function testGuestGets401(): void
    {
        $this->getJson('/api/test/private')->assertUnauthorized()->assertExactJson($this->error('Сначала войди в аккаунт.'));
    }

    public function testThrottleKeepsRetryAfter(): void
    {
        $this->getJson('/api/test/throttled')->assertOk();

        $this->getJson('/api/test/throttled')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertExactJson($this->error('Слишком много попыток. Подожди минуту и попробуй снова.'));
    }

    public function testCrashHidesDetails(): void
    {
        $this->withoutExceptionHandling([RuntimeException::class]);

        $this->getJson('/api/test/crash')
            ->assertServerError()
            ->assertExactJson($this->error('На сервере что-то сломалось. Попробуй ещё раз чуть позже.'));
    }

    public function testAnswersInRequestedLanguage(): void
    {
        $this->getJson('/api/nope', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertNotFound()
            ->assertJsonPath('message', 'This address or record does not exist.');
    }

    public function testUnsupportedLanguageFallsBackToRussian(): void
    {
        $this->getJson('/api/nope', ['Accept-Language' => 'de'])->assertJsonPath('message', 'Такого адреса или записи нет.');
    }

    public function testJsonEvenWithoutAcceptHeader(): void
    {
        $this->get('/api/test/private', ['Accept' => 'text/html'])->assertUnauthorized()->assertJsonPath('status', 'error');
    }

    /**
     * @param array<string, list<string>> $errors
     *
     * @return array<string, mixed>
     */
    private function error(string $message, array $errors = []): array
    {
        return ['status' => 'error', 'message' => $message, 'data' => null, 'errors' => $errors];
    }
}

final class LessonLockedException extends ApiException
{
    protected const string TRANSLATION_KEY = 'exceptions.http.403';

    protected const int STATUS = 403;
}
