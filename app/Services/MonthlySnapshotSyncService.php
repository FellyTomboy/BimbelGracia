<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassStudent;
use App\Models\MonthlyStudentSnapshot;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class MonthlySnapshotSyncService
{
    /**
     * Sinkronisasi rekap jumlah presensi individu murid kelas (Grup)
     */
    public function syncClassStudents(int $month, int $year): void
    {
        $students = ClassStudent::all();
        
        foreach ($students as $student) {
            // Hitung kehadiran individu melalui relasi pivot yang baru
            $totalPresent = $student->sessions()
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->count();

            // SOLUSI: Menggunakan tabel khusus untuk rekap tagihan/absen individu
            // Jangan gunakan 'monthly_student_snapshots' agar tidak bentrok dengan dasbor
            DB::table('class_student_monthly_attendances')->updateOrInsert(
                [
                    'class_student_id' => $student->id, 
                    'month' => $month, 
                    'year' => $year
                ],
                [
                    'total_present' => $totalPresent,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    /**
     * Sinkronisasi data agregat dasbor bulanan
     */
    public function syncForEnrollment(Enrollment $enrollment): void
    {
        $periods = DB::table('enrollment_attendances')
            ->where('enrollment_id', $enrollment->id)
            ->distinct()
            ->get(['year', 'month']);

        if ($periods->isEmpty()) {
            return;
        }

        $periods->each(function ($p) {
            $year = (int) $p->year;
            $month = (int) $p->month;

            // 1. Hitung agregat murid privat (historically accurate)
            // Count students who were active during the specified month/year
            $firstDayOfMonth = sprintf('%04d-%02d-01', $year, $month);
            $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));
            
            $privateStudentsCount = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
                ->join('students', 'attendance_student.student_id', '=', 'students.id')
                ->where('enrollments.status', 'active')
                ->where('students.status', 'active')
                ->where('enrollment_attendances.month', $month)
                ->where('enrollment_attendances.year', $year)
                ->where('students.created_at', '<=', $lastDayOfMonth)
                ->where(function ($query) use ($firstDayOfMonth) {
                    $query->whereNull('students.deleted_at')
                          ->orWhere('students.deleted_at', '>=', $firstDayOfMonth);
                })
                ->selectRaw('COUNT(DISTINCT attendance_student.student_id) as cnt')
                ->value('cnt') ?? 0;

            // 2. Hitung agregat guru (historically accurate)
            // Count teachers who were active during the specified month/year
            $firstDayOfMonth = sprintf('%04d-%02d-01', $year, $month);
            $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));
            
            $teachersCount = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('teachers', 'enrollments.teacher_id', '=', 'teachers.id')
                ->where('enrollments.status', 'active')
                ->where('teachers.status', 'active')
                ->where('enrollment_attendances.month', $month)
                ->where('enrollment_attendances.year', $year)
                ->where('teachers.created_at', '<=', $lastDayOfMonth)
                ->where(function ($query) use ($firstDayOfMonth) {
                    $query->whereNull('teachers.deleted_at')
                          ->orWhere('teachers.deleted_at', '>=', $firstDayOfMonth);
                })
                ->selectRaw('COUNT(DISTINCT enrollments.teacher_id) as cnt')
                ->value('cnt') ?? 0;

            // 3. Hitung agregat murid kelas (historically accurate)
            // Count students who existed during the specified month/year
            $firstDayOfMonth = sprintf('%04d-%02d-01', $year, $month);
            $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));
            
            $classStudentsCount = DB::table('class_students')
                ->where('created_at', '<=', $lastDayOfMonth)
                ->where(function ($query) use ($firstDayOfMonth) {
                    $query->whereNull('deleted_at')
                          ->orWhere('deleted_at', '>=', $firstDayOfMonth);
                })
                ->count();

            // 4. Simpan ke tabel dasbor agregat murid
            DB::table('monthly_student_snapshots')->updateOrInsert(
                ['year' => $year, 'month' => $month],
                [
                    'private_students_count' => $privateStudentsCount,
                    'class_students_count' => $classStudentsCount,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );

            // 5. Simpan ke tabel dasbor agregat guru
            DB::table('monthly_teacher_snapshots')->updateOrInsert(
                ['year' => $year, 'month' => $month],
                [
                    'teachers_count' => $teachersCount,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );

            // 6. Opsional: Jalankan sinkronisasi absen individu otomatis
            // Setiap kali rekap agregat berjalan, rekap individu juga akan diperbarui.
            $this->syncClassStudents($month, $year);
        });
    }
}