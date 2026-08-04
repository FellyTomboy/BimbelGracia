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
            $table->json('pricing_tiers')->nullable()->after('teacher_rate');
        });

        // Migrate existing data: set pricing_tiers = {1: parent_rate} and {1: teacher_rate}
        DB::table('enrollments')
            ->whereNull('pricing_tiers')
            ->orderBy('id')
            ->each(function ($enrollment) {
                $tiers = [
                    'parent_rate' => ['1' => (int) $enrollment->parent_rate],
                    'teacher_rate' => ['1' => (int) $enrollment->teacher_rate],
                ];
                DB::table('enrollments')
                    ->where('id', $enrollment->id)
                    ->update(['pricing_tiers' => json_encode($tiers)]);
            });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('pricing_tiers');
        });
    }
};