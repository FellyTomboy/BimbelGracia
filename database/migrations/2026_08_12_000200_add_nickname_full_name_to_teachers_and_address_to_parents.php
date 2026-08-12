<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'nickname')) {
                $table->string('nickname')->nullable()->after('name');
            }
            if (! Schema::hasColumn('teachers', 'full_name')) {
                $table->string('full_name')->nullable()->after('nickname');
            }
        });

        DB::table('teachers')->whereNotNull('name')->cursor()->each(function ($teacher) {
            $nickname = trim((string) ($teacher->nickname ?? '')) ?: trim((string) ($teacher->name ?? '')) ?: null;
            $fullName = trim((string) ($teacher->full_name ?? '')) ?: null;

            DB::table('teachers')->where('id', $teacher->id)->update([
                'nickname' => $nickname,
                'full_name' => $fullName,
            ]);
        });

        Schema::table('parents', function (Blueprint $table) {
            if (! Schema::hasColumn('parents', 'address')) {
                $table->text('address')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'nickname')) {
                $table->dropColumn('nickname');
            }
            if (Schema::hasColumn('teachers', 'full_name')) {
                $table->dropColumn('full_name');
            }
        });

        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
