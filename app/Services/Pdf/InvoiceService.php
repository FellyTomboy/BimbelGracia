<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\CalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function __construct(
        private CalculationService $calculationService
    ) {}

    /**
     * Generate and save invoice PDF for a student's monthly billing.
     */
    public function generateStudentInvoice(Student $student, int $month, int $year, Collection $attendances): string
    {
        $result = $this->calculationService->calculateStudentBilling($student, $month, $year, $attendances);

        $monthName = $this->monthName($month);

        // Check attendance penalty info for display
        $penaltyInfo = null;
        $enrollments = $attendances->groupBy('enrollment_id');
        foreach ($enrollments as $enrollmentId => $enrollmentAttendances) {
            $first = $enrollmentAttendances->first();
            $enrollment = $first->enrollment;
            $agreed = $enrollment?->agreed_sessions_per_month ?? 4;
            $studentTotalPresent = $enrollmentAttendances->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $totalSessions = $enrollmentAttendances->count();

            if ($enrollment && $enrollment->hasAttendancePenalty($totalSessions, $studentTotalPresent)) {
                $penaltyInfo = [
                    'program' => $enrollment->program?->name ?? '-',
                    'agreed' => $agreed,
                    'attended' => $studentTotalPresent,
                    'total_sessions' => $totalSessions,
                ];
            }
        }

        $pdf = Pdf::loadView('pdf.student-invoice', [
            'student' => $student,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $result['rows'],
            'grandTotal' => $result['grand_total'],
            'totalDiscount' => $result['total_discount'],
            'totalPenalty' => $result['total_penalty'],
            'penaltyInfo' => $penaltyInfo,
        ]);

        $parentName = $student->parent?->name ?? 'unknown';
        $parentSlug = str_replace(' ', '_', strtolower($parentName));
        $period = sprintf('%02d-%04d', $month, $year);
        $filename = sprintf('pdf/invoice/%s/%s.pdf', $parentSlug, $period);
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate and save combined invoice PDF for a parent with multiple students.
     */
    public function generateParentInvoice(Collection $students, int $month, int $year, Collection $attendances): string
    {
        $monthName = $this->monthName($month);
        $allRows = collect();
        $grandTotal = 0;
        $grandDiscount = 0;
        $grandPenalty = 0;
        $allPenalties = [];

        foreach ($students as $student) {
            $studentAttendances = $attendances->filter(fn ($a) => $a->students->contains($student->id));
            if ($studentAttendances->isEmpty()) continue;

            $result = $this->calculationService->calculateStudentBilling($student, $month, $year, $studentAttendances);

            // Tag each row with student name
            $taggedRows = $result['rows']->map(fn ($r) => array_merge($r, ['student_name' => $student->name]));
            $allRows = $allRows->concat($taggedRows);
            $grandTotal += $result['grand_total'];
            $grandDiscount += $result['total_discount'];
            $grandPenalty += $result['total_penalty'];

            // Check penalty info per student
            $enrollments = $studentAttendances->groupBy('enrollment_id');
            foreach ($enrollments as $enrollmentId => $enrollmentAttendances) {
                $first = $enrollmentAttendances->first();
                $enrollment = $first->enrollment;
                $agreed = $enrollment?->agreed_sessions_per_month ?? 4;
                $studentTotalPresent = $enrollmentAttendances->sum(function (MonthlyAttendance $attendance) use ($student) {
                    $s = $attendance->students->firstWhere('id', $student->id);
                    return (int) ($s?->pivot?->total_present ?? 0);
                });
                $totalSessions = $enrollmentAttendances->count();

                if ($enrollment && $enrollment->hasAttendancePenalty($totalSessions, $studentTotalPresent)) {
                    $allPenalties[] = [
                        'student' => $student->name,
                        'program' => $enrollment->program?->name ?? '-',
                        'agreed' => $agreed,
                        'attended' => $studentTotalPresent,
                        'total_sessions' => $totalSessions,
                    ];
                }
            }
        }

        $parentName = $students->first()?->parent?->name ?? 'Orang Tua';

        $pdf = Pdf::loadView('pdf.parent-invoice', [
            'parentName' => $parentName,
            'students' => $students,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $allRows,
            'grandTotal' => $grandTotal,
            'totalDiscount' => $grandDiscount,
            'totalPenalty' => $grandPenalty,
            'penalties' => $allPenalties,
        ]);

        $parentSlug = str_replace(' ', '_', strtolower($parentName));
        $period = sprintf('%02d-%04d', $month, $year);
        $filename = sprintf('pdf/invoice/%s/%s.pdf', $parentSlug, $period);
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate and save salary slip PDF for a teacher.
     */
    public function generateTeacherSalarySlip(Teacher $teacher, int $month, int $year, Collection $attendances): string
    {
        $result = $this->calculationService->calculateTeacherSalary($teacher->id, $month, $year, $attendances);

        $monthName = $this->monthName($month);

        $totalLateCount = $result['rows']->sum('late_count');

        $pdf = Pdf::loadView('pdf.teacher-salary', [
            'teacher' => $teacher,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $result['rows'],
            'grandTotal' => $result['grand_total'],
            'totalPenalty' => $result['total_penalty'],
            'totalLateCount' => $totalLateCount,
            'finalTotal' => $result['final_total'],
        ]);

        $teacherSlug = str_replace(' ', '_', strtolower($teacher->name));
        $period = sprintf('%02d-%04d', $month, $year);
        $filename = sprintf('pdf/salary/%s/%s.pdf', $teacherSlug, $period);
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    private function monthName(int $month): string
    {
        $names = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $names[$month] ?? 'Bulan';
    }
}