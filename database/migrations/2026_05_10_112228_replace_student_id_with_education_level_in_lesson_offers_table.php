<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_offers', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
            $table->string('education_level', 50)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_offers', function (Blueprint $table) {
            $table->dropColumn('education_level');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
        });
    }
};
