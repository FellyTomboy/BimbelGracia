<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_student_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('class_student_sessions', 'class_student_id')) {
                // Drop foreign key constraint first before dropping the column
                $table->dropForeign(['class_student_id']);
                $table->dropColumn('class_student_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_student_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('class_student_sessions', 'class_student_id')) {
                $table->foreignId('class_student_id')->constrained('class_students')->cascadeOnDelete();
            }
        });
    }
};
