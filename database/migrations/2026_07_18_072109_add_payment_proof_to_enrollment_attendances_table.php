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
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('teacher_payment_status');
            $table->string('payment_proof_status')->default('none')->after('payment_proof');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'payment_proof_status']);
        });
    }
};
