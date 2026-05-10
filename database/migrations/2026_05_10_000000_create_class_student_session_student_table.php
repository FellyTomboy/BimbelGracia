<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student_session_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_student_session_id')
                ->constrained('class_student_sessions')
                ->cascadeOnDelete()
                ->name('css_id_foreign'); // Nama dipendekkan agar tidak error panjang karakter
            $table->foreignId('class_student_id')
                ->constrained('class_students')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student_session_student');
    }
};