<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdf_access_tokens', function (Blueprint $table) {
            $table->foreignId('generated_by')
                ->nullable()
                ->after('entity_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pdf_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['generated_by']);
            $table->dropColumn('generated_by');
        });
    }
};
