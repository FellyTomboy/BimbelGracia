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
        // 1. Create parents table
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // 2. Add parent_id to students first (nullable)
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id');
        });

        // 3. Migrate data: for each unique phone, create parent & link students
        DB::transaction(function () {
            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->select('students.id as student_id', 'students.user_id', 'users.phone', 'users.name as user_name')
                ->whereNull('students.deleted_at')
                ->get();

            $phoneToParentId = [];

            foreach ($students as $student) {
                $phone = $student->phone;

                if (!isset($phoneToParentId[$phone])) {
                    // Update the user's role to 'parent'
                    DB::table('users')->where('id', $student->user_id)->update(['role' => 'parent']);

                    // Create parent record
                    $parentId = DB::table('parents')->insertGetId([
                        'user_id' => $student->user_id,
                        'name' => $student->user_name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $phoneToParentId[$phone] = $parentId;
                }

                // Link student to parent
                DB::table('students')
                    ->where('id', $student->student_id)
                    ->update(['parent_id' => $phoneToParentId[$phone]]);
            }
        });

        // 4. Now drop old columns
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('whatsapp');
            $table->dropColumn('whatsapp_primary');
            $table->dropColumn('whatsapp_secondary');
        });

        // 5. Make parent_id NOT NULL after data migration
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable(false)->change();
            $table->foreign('parent_id')->references('id')->on('parents')->cascadeOnDelete();
        });

        // 6. Convert JSON name to plain string
        // SQLite doesn't support JSON functions natively, so we handle it differently
        $students = DB::table('students')->whereNotNull('name')->get();
        foreach ($students as $student) {
            $name = $student->name;
            // Try to decode JSON array
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                $plainName = implode(', ', $decoded);
                DB::table('students')->where('id', $student->id)->update(['name' => $plainName]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('whatsapp', 20)->nullable();
            $table->string('whatsapp_primary', 20)->nullable();
            $table->string('whatsapp_secondary', 20)->nullable();
        });

        Schema::dropIfExists('parents');
    }
};