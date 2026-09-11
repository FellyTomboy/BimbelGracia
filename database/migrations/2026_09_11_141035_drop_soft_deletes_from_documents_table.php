<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Force-delete soft-deleted rows (no longer recoverable without SoftDeletes)
        DB::table('documents')->whereNotNull('deleted_at')->delete();

        // Drop the deleted_at column
        Schema::table('documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
