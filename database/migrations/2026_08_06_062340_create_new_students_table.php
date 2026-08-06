<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp', 20)->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_whatsapp', 20)->nullable();
            $table->string('school')->nullable();
            $table->string('grade')->nullable();
            $table->string('division')->nullable(); // TK/SD/SMP/SMA/UTBK
            $table->text('notes')->nullable();
            $table->string('token', 32)->unique(); // unique link token
            $table->boolean('converted')->default(false); // sudah jadi student?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_students');
    }
};