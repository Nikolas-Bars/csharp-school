<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Курсов несколько: описание и порядок для главной. Заполняет course:import из course.json и
// courses.json; до первого импорта описание пустое
return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', static function (Blueprint $table): void {
            $table->json('description')->nullable()->after('title');
            $table->unsignedSmallInteger('position')->default(0)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('courses', static function (Blueprint $table): void {
            $table->dropColumn(['description', 'position']);
        });
    }
};
