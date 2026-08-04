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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('agreed_sessions_per_month')->default(4)->after('teacher_rate');
        });

        // Set default for existing records: 4 sessions per month
        DB::table('enrollments')->whereNull('agreed_sessions_per_month')->update(['agreed_sessions_per_month' => 4]);
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('agreed_sessions_per_month');
        });
    }
};