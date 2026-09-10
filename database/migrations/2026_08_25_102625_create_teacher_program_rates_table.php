<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_program_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rate')->default(0);
            $table->timestamps();

            $table->unique(['teacher_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_program_rates');
    }
};
