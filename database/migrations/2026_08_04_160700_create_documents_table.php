<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable(); // pdf, docx, etc
            $table->unsignedInteger('file_size')->nullable(); // bytes
            $table->string('access_type', 20)->default('teacher'); // 'teacher' or 'password'
            $table->string('access_password')->nullable(); // hashed password for password mode
            $table->string('access_password_plain')->nullable(); // plain text for admin to see
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};