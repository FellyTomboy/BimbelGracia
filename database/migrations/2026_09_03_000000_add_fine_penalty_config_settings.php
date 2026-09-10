<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.late_penalty_type'],
            ['value' => 'percent', 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.late_penalty_value'],
            ['value' => '10', 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.attendance_penalty_type'],
            ['value' => 'fixed', 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.attendance_penalty_value'],
            ['value' => '5000', 'created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'fine.late_penalty_type',
            'fine.late_penalty_value',
            'fine.attendance_penalty_type',
            'fine.attendance_penalty_value',
        ])->delete();
    }
};
