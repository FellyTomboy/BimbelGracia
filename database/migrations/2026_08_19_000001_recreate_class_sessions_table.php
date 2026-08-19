<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('class_session_student');
        Schema::dropIfExists('class_sessions');

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'session_date']);
        });

        Schema::create('class_session_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_session_id', 'teacher_id'], 'cst_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_teacher');
        Schema::dropIfExists('class_sessions');
    }
};
