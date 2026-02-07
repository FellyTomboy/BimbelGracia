<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'whatsapp_primary')) {
                $table->string('whatsapp_primary', 32)->nullable();
            }
            if (! Schema::hasColumn('students', 'whatsapp_secondary')) {
                $table->string('whatsapp_secondary', 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'whatsapp_primary')) {
                $table->dropColumn('whatsapp_primary');
            }
            if (Schema::hasColumn('students', 'whatsapp_secondary')) {
                $table->dropColumn('whatsapp_secondary');
            }
        });
    }
};
