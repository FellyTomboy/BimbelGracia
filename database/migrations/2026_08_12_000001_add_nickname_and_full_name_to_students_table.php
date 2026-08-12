<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'name')) {
            if (!Schema::hasColumn('students', 'nickname')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->string('nickname')->nullable()->after('id');
                });
            }

            if (!Schema::hasColumn('students', 'full_name')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->string('full_name')->nullable()->after('nickname');
                });
            }

            $students = DB::table('students')->get();

            foreach ($students as $student) {
                $nickname = trim((string) ($student->nickname ?? $student->name ?? ''));
                if ($nickname === '') {
                    $nickname = 'Tanpa nama';
                }

                DB::table('students')->where('id', $student->id)->update([
                    'nickname' => $nickname,
                    'full_name' => $student->full_name ?? null,
                ]);
            }

            return;
        }

        // If 'name' column exists but nickname/full_name don't, add them
        if (!Schema::hasColumn('students', 'nickname')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('nickname')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('full_name')->nullable()->after('nickname');
            });
        }

        $students = DB::table('students')->get();

        foreach ($students as $student) {
            $nickname = trim((string) ($student->nickname ?? $student->name ?? ''));
            if ($nickname === '') {
                $nickname = 'Tanpa nama';
            }

            DB::table('students')->where('id', $student->id)->update([
                'nickname' => $nickname,
                'full_name' => $student->full_name ?? null,
            ]);
        }

        // Only drop 'name' column if it exists
        if (Schema::hasColumn('students', 'name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('students', 'name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('name')->nullable()->after('id');
            });
        }
    }
};
