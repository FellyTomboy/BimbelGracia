<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->json('dates')->nullable();
            $table->unsignedInteger('total_lessons')->default(0);
            $table->text('notes')->nullable();
            $table->string('status_validation', 20)->default('pending');
            $table->string('parent_payment_status', 20)->nullable();
            $table->string('teacher_payment_status', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enrollment_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_attendances');
    }
};
