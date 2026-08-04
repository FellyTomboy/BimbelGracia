<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlySnapshotSyncService
{
    /**
     * Sync all snapshots for the current month.
     */
    public function syncAll(): void
    {
        $now = Carbon::now();
        $this->syncForPeriod((int) $now->month, (int) $now->year);
    }

    /**
     * Sync snapshots for a specific month/year.
     */
    public function syncForPeriod(int $month, int $year): void
    {
        $this->syncStudentSnapshotsForPeriod($month, $year);
        $this->syncTeacherSnapshotsForPeriod($month, $year);
    }

    /**
     * Sync student snapshot for a specific month/year.
     */
    public function syncStudentSnapshotsForPeriod(int $month, int $year): void
    {
        $privateStudentsCount = Student::query()
            ->where('students.status', 'active')
            ->whereNull('students.deleted_at')
            ->count();

        DB::table('monthly_student_snapshots')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            [
                'private_students_count' => $privateStudentsCount,
                'class_students_count' => 0,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Sync teacher snapshot for a specific month/year.
     */
    public function syncTeacherSnapshotsForPeriod(int $month, int $year): void
    {
        $teachersCount = Teacher::query()
            ->where('teachers.status', 'active')
            ->whereNull('teachers.deleted_at')
            ->count();

        DB::table('monthly_teacher_snapshots')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            [
                'teachers_count' => $teachersCount,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}