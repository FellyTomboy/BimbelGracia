<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move existing `name` data to `full_name` where full_name is empty
        DB::table('teachers')
            ->whereNotNull('name')
            ->where(function ($q) {
                $q->whereNull('full_name')
                  ->orWhere('full_name', '');
            })
            ->update(['full_name' => DB::raw('name')]);

        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'name')) {
                $table->string('name')->nullable()->after('nickname');
            }
        });

        // Restore name from full_name
        DB::table('teachers')
            ->whereNotNull('full_name')
            ->update(['name' => DB::raw('full_name')]);
    }
};
