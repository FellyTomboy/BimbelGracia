<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys that depend on the unique index
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['validated_by']);
        });

        Schema::table('enrollment_attendances', function (Blueprint $table) {
            // Drop unique constraint (per-session, not per-month anymore)
            $table->dropUnique(['enrollment_id', 'month', 'year']);

            // Drop old monthly columns
            $table->dropColumn(['dates', 'total_lessons']);

            // Add new per-session columns (nullable for existing data)
            $table->date('lesson_date')->nullable()->after('year');
            $table->string('image')->nullable()->after('notes');
        });

        // Recreate foreign keys
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Change status_validation via raw SQL
        DB::statement("ALTER TABLE enrollment_attendances MODIFY COLUMN status_validation VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['validated_by']);
        });

        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn(['lesson_date', 'image']);
            $table->json('dates')->nullable();
            $table->unsignedInteger('total_lessons')->default(0);
            $table->unique(['enrollment_id', 'month', 'year']);
        });

        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
