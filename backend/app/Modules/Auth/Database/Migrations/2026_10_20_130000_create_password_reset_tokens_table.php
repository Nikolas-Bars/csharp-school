<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Таблица сброса пароля в формате Laravel (auth.passwords.users.table): одна ссылка на почту.
// Истёкшие строки удаляет auth:clear-resets (routes/console.php)
return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_tokens', static function (Blueprint $table): void {
            $table->string('email')->primary();
            // SHA-256 токена из ссылки: сам токен есть только в письме
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
