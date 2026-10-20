<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Данных об ученике — минимум (среди учеников есть несовершеннолетние): ник, почта, язык.
// Настоящее имя и возраст не храним
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            // Как ученика видят другие; не обязательно уникален
            $table->string('nickname', 32);
            $table->string('email')->unique();
            // Язык интерфейса и уроков, один из app.supported_locales
            $table->string('locale', 8)->default('ru');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
