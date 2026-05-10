<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student_monthly_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_student_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_present')->default(0);
            $table->timestamps();

            $table->unique(['class_student_id', 'month', 'year'], 'cs_ma_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student_monthly_attendances');
    }
};
