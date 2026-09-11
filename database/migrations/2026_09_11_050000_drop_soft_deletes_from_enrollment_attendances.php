<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pivotDeleted = DB::table('attendance_student')
            ->whereIn('attendance_id', DB::table('enrollment_attendances')->whereNotNull('deleted_at')->pluck('id'))
            ->delete();

        $attendanceDeleted = DB::table('enrollment_attendances')->whereNotNull('deleted_at')->delete();

        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        \Illuminate\Support\Facades\Log::info("drop_soft_deletes_from_enrollment_attendances: pivots=$pivotDeleted attendances=$attendanceDeleted");
    }

    public function down(): void
    {
        Schema::table('enrollment_attendances', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
