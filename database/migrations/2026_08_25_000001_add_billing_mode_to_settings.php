<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::table('settings')->updateOrInsert(
            ['key' => 'billing.mode'],
            ['value' => 'monthly', 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        \DB::table('settings')->where('key', 'billing.mode')->delete();
    }
};
