<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MonthlySnapshotSyncService
{
    public function syncForEnrollment(Enrollment $enrollment): void
    {
        $periods = DB::table('enrollment_attendances')
            ->where('enrollment_id', $enrollment->id)
            ->distinct()
            ->get(['year', 'month']);

        if ($periods->isEmpty()) {
            return;
        }

        $periods->each(function ($p) use ($enrollment) {
            $year = (int) $p->year;
            $month = (int) $p->month;

            // Murid privat (berdasarkan enrollment active), bukan attendance valid
            $privateStudentsCount = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
                ->join('students', 'attendance_student.student_id', '=', 'students.id')
                ->where('enrollments.status', 'active')
                ->where('students.status', 'active')
                ->where('enrollment_attendances.month', $month)
                ->where('enrollment_attendances.year', $year)
                ->selectRaw('COUNT(DISTINCT attendance_student.student_id) as cnt')
                ->value('cnt') ?? 0;

            // Guru per bulan (berdasarkan enrollment active)
            $teachersCount = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('teachers', 'enrollments.teacher_id', '=', 'teachers.id')
                ->where('enrollments.status', 'active')
                ->where('teachers.status', 'active')
                ->where('enrollment_attendances.month', $month)
                ->where('enrollment_attendances.year', $year)
                ->selectRaw('COUNT(DISTINCT enrollments.teacher_id) as cnt')
                ->value('cnt') ?? 0;

            $classStudentsCount = DB::table('class_students')
                ->where('status', 'active')
                ->count();

            DB::table('monthly_student_snapshots')->updateOrInsert(
                ['year' => $year, 'month' => $month],
                [
                    'private_students_count' => $privateStudentsCount,
                    'class_students_count' => $classStudentsCount,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('monthly_teacher_snapshots')->updateOrInsert(
                ['year' => $year, 'month' => $month],
                [
                    'teachers_count' => $teachersCount,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });
    }

    public function syncClassStudentsCountsForAllPeriods(): void
    {
        $periods = DB::table('enrollment_attendances')
            ->distinct()
            ->get(['year', 'month']);

        if ($periods->isEmpty()) {
            return;
        }

        $classStudentsCount = DB::table('class_students')
            ->where('status', 'active')
            ->count();

        foreach ($periods as $p) {
            $year = (int) $p->year;
            $month = (int) $p->month;

            DB::table('monthly_student_snapshots')->updateOrInsert(
                ['year' => $year, 'month' => $month],
                [
                    'class_students_count' => $classStudentsCount,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
