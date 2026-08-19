<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollment_attendances', 'class_session_id')) {
                $table->foreignId('class_session_id')
                    ->nullable()
                    ->after('session_teacher_id')
                    ->constrained('class_sessions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('enrollment_attendances', 'class_session_id')) {
                $table->dropConstrainedForeignId('class_session_id');
            }
        });
    }
};
