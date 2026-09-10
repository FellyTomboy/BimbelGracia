<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_session_teacher', function (Blueprint $table) {
            $table->unsignedInteger('rate')->default(0)->after('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_session_teacher', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
    }
};
