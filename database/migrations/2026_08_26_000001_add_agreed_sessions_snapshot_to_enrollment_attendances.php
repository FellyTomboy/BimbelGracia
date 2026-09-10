<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->unsignedTinyInteger('agreed_sessions_per_month')
                ->nullable()
                ->after('teacher_rate');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn('agreed_sessions_per_month');
        });
    }
};
