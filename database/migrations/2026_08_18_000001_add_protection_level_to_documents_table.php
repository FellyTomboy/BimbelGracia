<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // 'standard' = guru boleh download bebas
            // 'strict'   = hanya bisa dilihat via viewer (signed URL), download diblokir total
            $table->string('protection_level', 20)->default('standard')->after('access_type');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('protection_level');
        });
    }
};
