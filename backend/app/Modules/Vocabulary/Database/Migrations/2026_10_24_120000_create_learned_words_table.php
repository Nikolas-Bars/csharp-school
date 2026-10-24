<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Словарь ученика: выученные слова и когда их повторять. Слово — ID карточки модуля Course без
// внешнего ключа (чужая таблица); пользователь — общий справочник, внешний ключ можно
return new class extends Migration {
    public function up(): void
    {
        Schema::create('learned_words', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('word_id')->index();
            // Коробка интервального повторения: 0 — новое или забытое, 4 — выучено (раз в 30 дней)
            $table->unsignedTinyInteger('box');
            $table->timestamp('due_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            // Слово в словаре одно: повторное добавление ничего не меняет
            $table->unique(['user_id', 'word_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learned_words');
    }
};
