<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassStudent;
use App\Models\Enrollment;
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
        // 1. Private students: active students with active enrollments
        $privateStudentsCount = Student::query()
            ->where('students.status', 'active')
            ->whereNull('students.deleted_at')
            ->whereHas('enrollments', fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))
            ->count();

        // 2. Class students: active class_students
        $classStudentsCount = ClassStudent::query()
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        // 3. Teachers: active teachers with active enrollments
        $teachersCount = Teacher::query()
            ->where('teachers.status', 'active')
            ->whereNull('teachers.deleted_at')
            ->whereHas('enrollments', fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))
            ->count();

        // 4. Save to monthly_student_snapshots
        DB::table('monthly_student_snapshots')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            [
                'private_students_count' => $privateStudentsCount,
                'class_students_count' => $classStudentsCount,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );

        // 5. Save to monthly_teacher_snapshots
        DB::table('monthly_teacher_snapshots')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            [
                'teachers_count' => $teachersCount,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );

        // 6. Sync class student individual attendances
        $this->syncClassStudents($month, $year);
    }

    /**
     * Sync individual attendance counts for class students.
     */
    public function syncClassStudents(int $month, int $year): void
    {
        $students = ClassStudent::all();

        foreach ($students as $student) {
            $totalPresent = $student->sessions()
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->count();

            DB::table('class_student_monthly_attendances')->updateOrInsert(
                [
                    'class_student_id' => $student->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'total_present' => $totalPresent,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }
}