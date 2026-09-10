<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();
            $table->string('entity_type', 20); // 'parent' | 'teacher'
            $table->unsignedBigInteger('entity_id');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('storage_path'); // path relative to public disk root, e.g. pdf/invoice/parent_84/08-2026.pdf
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_access_tokens');
    }
};
