<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_attendances', 'parent_rate')) {
                $table->unsignedInteger('parent_rate')->nullable()->default(0)->after('validated_by');
            }

            if (! Schema::hasColumn('enrollment_attendances', 'teacher_rate')) {
                $table->unsignedInteger('teacher_rate')->nullable()->default(0)->after('parent_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn(['parent_rate', 'teacher_rate']);
        });
    }
};
