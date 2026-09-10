<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('filename', 255);
            $table->timestamp('regenerated_at')->nullable();
            $table->timestamps();
            $table->unique(['parent_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
