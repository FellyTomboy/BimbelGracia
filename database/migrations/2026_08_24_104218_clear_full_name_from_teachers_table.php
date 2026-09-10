<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('teachers')->update(['full_name' => null]);
    }

    public function down(): void
    {
        // Tidak dapat dipulihkan karena data original sudah tidak ada
    }
};
