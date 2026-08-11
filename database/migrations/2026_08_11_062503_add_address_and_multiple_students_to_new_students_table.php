<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_students', function (Blueprint $table) {
            $table->text('address')->nullable()->after('parent_whatsapp');
            $table->json('students_data')->nullable()->after('notes');
        });

        // Make name nullable since it'll be moved to students_data
        Schema::table('new_students', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('whatsapp', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('new_students', function (Blueprint $table) {
            $table->dropColumn(['address', 'students_data']);
        });

        Schema::table('new_students', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('whatsapp', 20)->nullable(false)->change();
        });
    }
};