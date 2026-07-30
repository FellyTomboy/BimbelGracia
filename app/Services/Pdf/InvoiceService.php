<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\MonthlyAttendance;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate and save invoice PDF for a student's monthly billing.
     */
    public function generateStudentInvoice(Student $student, int $month, int $year, Collection $attendances): string
    {
        $rows = $attendances->map(function (MonthlyAttendance $attendance) use ($student) {
            $s = $attendance->students->firstWhere('id', $student->id);
            $present = (int) ($s?->pivot?->total_present ?? 0);
            $rate = $attendance->enrollment?->parent_rate ?? 0;
            $total = $present * $rate;

            return [
                'program' => $attendance->enrollment?->program?->name ?? '-',
                'teacher' => $attendance->enrollment?->teacher?->name ?? '-',
                'count' => $present,
                'rate' => $rate,
                'total' => $total,
            ];
        });

        $grandTotal = $rows->sum('total');

        $monthName = $this->monthName($month);

        $pdf = Pdf::loadView('pdf.student-invoice', [
            'student' => $student,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $rows,
            'grandTotal' => $grandTotal,
        ]);

        $filename = sprintf('invoice/%s/%s_%04d-%02d.pdf', $student->id, str_replace(' ', '_', $student->display_name), $year, $month);
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate and save salary slip PDF for a teacher.
     */
    public function generateTeacherSalarySlip(\App\Models\Teacher $teacher, int $month, int $year, Collection $attendances): string
    {
        $rows = $attendances->map(function (MonthlyAttendance $attendance) {
            $studentNames = $attendance->students->map->display_name->implode(', ');
            $rate = $attendance->enrollment?->teacher_rate ?? 0;
            $totalCount = 1;
            $lateCount = $attendance->status_validation === 'terlambat' ? 1 : 0;
            $grossTotal = $totalCount * $rate;
            $penalty = (int) ($lateCount * $rate * 0.1);

            return [
                'student' => $studentNames ?: '-',
                'program' => $attendance->enrollment?->program?->name ?? '-',
                'count' => $totalCount,
                'rate' => $rate,
                'total' => $grossTotal,
                'penalty' => $penalty,
                'late_count' => $lateCount,
            ];
        });

        $grandTotal = $rows->sum('total');
        $totalPenalty = $rows->sum('penalty');
        $finalTotal = $grandTotal - $totalPenalty;

        $monthName = $this->monthName($month);

        $pdf = Pdf::loadView('pdf.teacher-salary', [
            'teacher' => $teacher,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'totalPenalty' => $totalPenalty,
            'finalTotal' => $finalTotal,
        ]);

        $filename = sprintf('salary/%s/%s_%04d-%02d.pdf', $teacher->id, str_replace(' ', '_', $teacher->name), $year, $month);
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