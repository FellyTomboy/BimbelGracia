<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\BankAccount;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceFineService;
use App\Services\CalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function __construct(
        private CalculationService $calculationService,
        private AttendanceFineService $fineService,
    ) {}

    /**
     * Generate and save invoice PDF for a student's monthly billing.
     * Returns ['storage_path' => ..., 'token' => ...].
     */
    public function generateStudentInvoice(Student $student, int $month, int $year, Collection $attendances, ?string $existingFilename = null): array
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
            'attendancePenaltyDisplayLabel' => $this->fineService->getAttendancePenaltyDisplayLabel(),
        ])->setPaper('a4', 'portrait');

        $student->loadMissing('parent');
        $parent = $student->parent;
        $parentId = $parent?->id;
        $period = sprintf('%02d-%04d', $month, $year);
        $filename = $existingFilename
            ?? sprintf('pdf/invoice/parent_%s/Tagihan_%s_%s.pdf', $parentId ?? 'unknown', $period, bin2hex(random_bytes(8)));
        Storage::disk('public')->put($filename, $pdf->output());

        return ['storage_path' => $filename];
    }

    /**
     * Generate and save combined invoice PDF for a parent with multiple students.
     * Returns ['storage_path' => ..., 'token' => ...].
     */
    public function generateParentInvoice(Collection $students, int $month, int $year, Collection $attendances, ?string $existingFilename = null): array
    {
        $monthName = $this->monthName($month);
        $allRows = collect();
        $grandGross = 0;
        $grandDiscount = 0;
        $grandPenalty = 0;
        $allPenalties = [];

        foreach ($students as $student) {
            $studentAttendances = $attendances->filter(fn ($a) => $a->students->contains($student->id));
            if ($studentAttendances->isEmpty()) continue;

            $result = $this->calculationService->calculateStudentBilling($student, $month, $year, $studentAttendances);

            // Tag each row with student name
            $taggedRows = $result['rows']->map(function ($r) use ($student) {
                $r['student_name'] = $student->display_name;
                return $r;
            });
            $allRows = $allRows->concat($taggedRows);
            $grandGross += $result['rows']->sum('subtotal');
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
                        'student' => $student->display_name,
                        'program' => $enrollment->program?->name ?? '-',
                        'agreed' => $agreed,
                        'attended' => $studentTotalPresent,
                        'total_sessions' => $totalSessions,
                    ];
                }
            }
        }

        // subtotal already includes attendance penalty baked into the rate (inflatedSubtotal).
        // grandTotal = pre-discount subtotals minus discounts. Penalty is not added back.
        $grandTotal = $grandGross - $grandDiscount;

        $parentName = $students->first()?->parent?->name ?? 'Orang Tua';
        $parentId = $students->first()?->parent?->id;
        $period = sprintf('%02d-%04d', $month, $year);

        $pdf = Pdf::loadView('pdf.parent-invoice', [
            'parentName' => $parentName,
            'students' => $students,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $allRows,
            'grandGross' => $grandGross,
            'grandDiscount' => $grandDiscount,
            'grandPenalty' => $grandPenalty,
            'grandTotal' => $grandTotal,
            'penalties' => $allPenalties,
            'attendancePenaltyDisplayLabel' => $this->fineService->getAttendancePenaltyDisplayLabel(),
            'bankAccounts' => BankAccount::where('status', 'active')->orderBy('id')->get(),
        ])->setPaper('a4', 'portrait');

        // Use parent ID for stable path — won't break when parent name changes
        $filename = $existingFilename
            ?? sprintf('pdf/invoice/parent_%s/Tagihan_%s_%s.pdf', $parentId ?? 'unknown', $period, bin2hex(random_bytes(8)));
        Storage::disk('public')->put($filename, $pdf->output());

        return ['storage_path' => $filename];
    }

    /**
     * Generate and save salary slip PDF for a teacher.
     * Returns ['storage_path' => ..., 'token' => ...].
     */
    public function generateTeacherSalarySlip(Teacher $teacher, int $month, int $year, Collection $attendances, ?string $existingFilename = null): array
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
            'latePenaltyDisplayLabel' => $this->fineService->getLatePenaltyDisplayLabel(),
            'bankAccounts' => BankAccount::where('status', 'active')->orderBy('id')->get(),
        ])->setPaper('a4', 'portrait');

        $period = sprintf('%02d-%04d', $month, $year);
        $filename = $existingFilename
            ?? sprintf('pdf/salary/teacher_%s/Slip_Gaji_%s_%s.pdf', $teacher->id, $period, bin2hex(random_bytes(8)));
        Storage::disk('public')->put($filename, $pdf->output());

        return ['storage_path' => $filename];
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