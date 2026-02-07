<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_offers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('schedule_day', 20);
            $table->string('schedule_time', 10);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('contact_whatsapp', 32)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_offers');
    }
};
