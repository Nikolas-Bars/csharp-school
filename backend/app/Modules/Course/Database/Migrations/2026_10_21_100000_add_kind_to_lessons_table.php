<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Вид урока: lesson — обычный, test — контрольная модуля. Заполняет course:import из lesson.json
return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', static function (Blueprint $table): void {
            $table->string('kind', 16)->default('lesson')->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', static function (Blueprint $table): void {
            $table->dropColumn('kind');
        });
    }
};
