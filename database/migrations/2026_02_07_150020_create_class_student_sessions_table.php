<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_student_id')->constrained('class_students')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['session_date', 'class_student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student_sessions');
    }
};
