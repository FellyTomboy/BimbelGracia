<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->string('parent_review_status', 20)->nullable()->after('teacher_payment_status');
            $table->timestamp('parent_reviewed_at')->nullable()->after('parent_review_status');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn(['parent_review_status', 'parent_reviewed_at']);
        });
    }
};