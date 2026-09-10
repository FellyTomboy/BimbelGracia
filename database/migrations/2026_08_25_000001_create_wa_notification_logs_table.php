<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('parents')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->text('message_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['parent_id', 'month', 'year'], 'wa_log_parent_period_unique');
            $table->unique(['teacher_id', 'month', 'year'], 'wa_log_teacher_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_notification_logs');
    }
};
