<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Пройденные шаги урока: задача (по id) или проверка знаний (quiz). Урок — ID модуля Course без
// внешнего ключа (чужая таблица); пользователь — общий справочник, внешний ключ можно
return new class extends Migration {
    public function up(): void
    {
        Schema::create('completed_steps', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('lesson_id')->index();
            $table->string('step', 64);
            // Последний зачтённый код задачи — чтобы открыть его на другом устройстве
            $table->text('code')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();
            // Шаг засчитывается один раз: повтор только обновляет код
            $table->unique(['user_id', 'lesson_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completed_steps');
    }
};
