<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Карточки слов курса (шаг words уроков английского): по ним модуль Vocabulary ведёт словарь ученика.
// course:import пишет их по slug — id карточки не меняется, на него ссылается словарь. Урок переименовали
// или перенесли — старый удаляется, но карточка остаётся (lesson_id → null) и в той же транзакции импорта
// получает новый урок: словари учеников не теряют слово
return new class extends Migration {
    public function up(): void
    {
        Schema::create('words', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug', 64);
            $table->unsignedSmallInteger('position');
            $table->json('card');
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};
