<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_student_id')->constrained('class_students')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('discount_type', 20);
            $table->unsignedInteger('discount_value');
            $table->timestamps();

            $table->unique(['class_student_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student_discounts');
    }
};
