<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->string('parent_payment_status', 20)->default('unpaid')->after('status');
            $table->string('teacher_payment_status', 20)->default('unpaid')->after('parent_payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->dropColumn(['parent_payment_status', 'teacher_payment_status']);
        });
    }
};
