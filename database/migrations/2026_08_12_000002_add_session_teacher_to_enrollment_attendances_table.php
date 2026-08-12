<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_attendances', 'session_teacher_id')) {
                $table->foreignId('session_teacher_id')->nullable()->after('enrollment_id')->constrained('teachers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('enrollment_attendances', 'session_teacher_id')) {
                $table->dropConstrainedForeignId('session_teacher_id');
            }
        });
    }
};
