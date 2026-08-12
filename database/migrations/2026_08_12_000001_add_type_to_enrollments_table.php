<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'type')) {
                $table->string('type')->default('privat')->after('program_id');
            }

            if (Schema::hasColumn('enrollments', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
