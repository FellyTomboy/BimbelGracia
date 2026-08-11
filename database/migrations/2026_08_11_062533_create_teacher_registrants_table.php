<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_registrants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp', 20);
            $table->string('major')->nullable();
            $table->string('subjects')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_owner')->nullable();
            $table->string('token', 32)->unique();
            $table->boolean('converted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_registrants');
    }
};