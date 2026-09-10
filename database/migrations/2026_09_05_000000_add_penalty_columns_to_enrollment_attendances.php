<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->string('attendance_penalty_type')->nullable()->after('agreed_sessions_per_month');
            $table->integer('attendance_penalty_value')->nullable()->unsigned()->after('attendance_penalty_type');
            $table->string('late_penalty_type')->nullable()->after('attendance_penalty_value');
            $table->integer('late_penalty_value')->nullable()->unsigned()->after('late_penalty_type');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_penalty_type',
                'attendance_penalty_value',
                'late_penalty_type',
                'late_penalty_value',
            ]);
        });
    }
};
