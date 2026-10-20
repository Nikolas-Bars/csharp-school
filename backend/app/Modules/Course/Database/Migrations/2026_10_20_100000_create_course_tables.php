<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Курс → модули → уроки. Источник — файлы content/ (course:import), здесь — копия для чтения.
// Тексты — JSON по языкам {"ru": "…"}; шаги урока — JSON целиком: урок всегда читается весь
return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', static function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->json('title');
            $table->timestamps();
        });

        Schema::create('course_modules', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 64);
            $table->unsignedSmallInteger('position');
            $table->json('title');
            $table->json('description');
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
        });

        Schema::create('lessons', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 64);
            $table->unsignedSmallInteger('position');
            $table->json('title');
            $table->json('steps');
            $table->timestamps();
            $table->unique(['course_module_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
    }
};
