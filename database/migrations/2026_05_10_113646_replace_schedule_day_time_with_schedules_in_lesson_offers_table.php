<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_offers', function (Blueprint $table) {
            $table->dropColumn(['schedule_day', 'schedule_time']);
            $table->json('schedules')->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_offers', function (Blueprint $table) {
            $table->dropColumn('schedules');
            $table->string('schedule_day', 20);
            $table->string('schedule_time', 10);
        });
    }
};
