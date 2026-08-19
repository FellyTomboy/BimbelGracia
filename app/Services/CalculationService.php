<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentStudentDiscount;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use Illuminate\Support\Collection;

class CalculationService
{
    public function __construct(private AttendanceFineService $fineService) {}

    /**
     * Calculate parent billing for a student in a given month.
     * Returns grouped rows with rate, count, subtotal, discount, penalty.
     */
    public function calculateStudentBilling(Student $student, int $month, int $year, Collection $attendances): array
    {
        $attendances = $attendances->filter(function (MonthlyAttendance $attendance) use ($student) {
            $s = $attendance->students->firstWhere('id', $student->id);
            return ($s?->pivot?->total_present ?? 0) > 0;
        });

        $privatAttendances = $attendances->filter(fn (MonthlyAttendance $attendance) => $attendance->enrollment?->isPrivat());
        $kelasAttendances = $attendances->filter(fn (MonthlyAttendance $attendance) => $attendance->enrollment?->isKelas());

        $rows = collect();

        foreach ($privatAttendances->groupBy(function (MonthlyAttendance $attendance) use ($student) {
            $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($attendance->parent_rate ?? $attendance->enrollment?->getParentRateForCount($presentCount) ?? 0);
            return $attendance->enrollment_id . '-' . $rate . '-' . $presentCount;
        }) as $group) {
            $first = $group->first();
            $enrollment = $first->enrollment;
            $presentCount = $first->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($first->parent_rate ?? 0);
            $totalCount = $group->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $subtotal = $totalCount * $rate;
            $totalSessions = $group->count();
            $studentTotalPresent = $group->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $penalty = $this->resolveAttendancePenalty($enrollment, $totalCount, $studentTotalPresent);
            $discount = $this->resolveDiscountForBilling($enrollment, $student->id, $month, $year, $subtotal);

            $detailLabel = $presentCount > 1 ? ' (grup ' . $presentCount . ' siswa)' : '';

            $rows->push([
                'enrollment_id' => $first->enrollment_id,
                'program' => $enrollment?->program?->name ?? '-',
                'teacher' => $enrollment?->teacher?->name ?? '-',
                'count' => $totalCount,
                'rate' => $rate,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'penalty' => $penalty,
                'total' => $subtotal - $discount + $penalty,
                'detail' => $detailLabel,
                'present_count' => $presentCount,
                'type' => 'privat',
            ]);
        }

        foreach ($kelasAttendances->groupBy('enrollment_id') as $enrollmentId => $group) {
            $enrollment = $group->first()->enrollment;
            $agreedSessions = (int) ($enrollment->agreed_sessions_per_month ?? 4);
            $studentTotalPresent = $group->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $attendancePercent = $agreedSessions > 0 ? ($studentTotalPresent / $agreedSessions) * 100 : 0;
            $finalRate = $attendancePercent <= 50 ? (int) round((float) ($enrollment->parent_rate ?? 0) * 0.5) : (int) ($enrollment->parent_rate ?? 0);

            $discount = $this->resolveDiscountForBilling($enrollment, $student->id, $month, $year, $finalRate);

            $rows->push([
                'enrollment_id' => $enrollmentId,
                'program' => $enrollment?->program?->name ?? '-',
                'teacher' => '-',
                'count' => 1,
                'rate' => $finalRate,
                'subtotal' => $finalRate,
                'discount' => $discount,
                'penalty' => 0,
                'total' => $finalRate - $discount,
                'detail' => sprintf('Paket %d sesi - Hadir %d/%d (%d%%)', $agreedSessions, $studentTotalPresent, $agreedSessions, (int) $attendancePercent),
                'present_count' => 1,
                'type' => 'kelas',
            ]);
        }

        $rows = $rows->values();

        return [
            'rows' => $rows,
            'grand_total' => $rows->sum('total'),
            'total_discount' => $rows->sum('discount'),
            'total_penalty' => $rows->sum('penalty'),
        ];
    }

    private function resolveAttendancePenalty(?Enrollment $enrollment, int $totalSessions, int $studentTotalPresent): int
    {
        if (! $this->fineService->isAttendancePenaltyEnabled()) {
            return 0;
        }
        if (! $enrollment || ! $enrollment->hasAttendancePenalty($totalSessions, $studentTotalPresent)) {
            return 0;
        }
        return $totalSessions * 5000;
    }

    private function resolveDiscountForBilling(?Enrollment $enrollment, int $studentId, int $month, int $year, int $baseTotal): int
    {
        if (! $enrollment) {
            return 0;
        }
        $record = EnrollmentStudentDiscount::where('enrollment_id', $enrollment->id)
            ->where('student_id', $studentId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
        if (! $record) {
            return 0;
        }
        $type = strtolower((string) ($record->discount_type ?? ''));
        $value = (int) ($record->discount_value ?? 0);
        if ($type === 'percent' || $type === 'percentage') {
            $percent = max(0, min(100, $value));
            return (int) round($baseTotal * $percent / 100);
        }
        if ($type === 'amount') {
            return min($value, $baseTotal);
        }
        if ($type === 'final') {
            $finalTotal = max(0, min($value, $baseTotal));
            return $baseTotal - $finalTotal;
        }
        return 0;
    }

    /**
     * Calculate teacher salary for a teacher in a given month.
     * Returns grouped rows with rate, count, subtotal, penalty.
     */
    public function calculateTeacherSalary(int $teacherId, int $month, int $year, Collection $attendances): array
    {
        $attendances = $attendances->filter(function (MonthlyAttendance $attendance) use ($teacherId) {
            if ($attendance->enrollment?->isPrivat()) {
                return (int) ($attendance->enrollment->teacher_id ?? 0) === $teacherId;
            }

            return (int) ($attendance->session_teacher_id ?? 0) === $teacherId;
        })->filter(function (MonthlyAttendance $attendance) {
            return $attendance->students->sum(fn ($s) => (int) ($s->pivot->total_present ?? 0)) > 0;
        });

        $grouped = $attendances->groupBy(function (MonthlyAttendance $attendance) {
            $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($attendance->teacher_rate ?? $attendance->enrollment?->getTeacherRateForCount($presentCount) ?? 0);
            return $attendance->enrollment_id . '-' . $rate . '-' . $presentCount;
        });

        $rows = $grouped->map(function (Collection $group) {
            $first = $group->first();
            $enrollment = $first->enrollment;
            $presentCount = $group->first()->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($first->teacher_rate ?? 0);
            $studentNames = $group->flatMap(fn ($a) => $a->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->map->display_name)->unique()->implode(', ');
            $totalCount = $group->sum(fn ($a) => $a->students->sum(fn ($s) => (int) ($s->pivot->total_present ?? 0)));
            $lateCount = $group->filter(fn ($a) => $a->status_validation === 'terlambat')->count();
            $grossTotal = $totalCount * $rate;
            $penalty = $this->fineService->isLatePenaltyEnabled()
                ? (int) ($lateCount * $rate * 0.1)
                : 0;

            $countLabel = $presentCount . ' siswa';
            if ($presentCount > 1) $countLabel = 'grup ' . $presentCount . ' siswa';

            return [
                'enrollment_id' => $first->enrollment_id,
                'student' => $studentNames ?: '-',
                'program' => $enrollment?->program?->name ?? '-',
                'count' => $totalCount,
                'rate' => $rate,
                'total' => $grossTotal,
                'penalty' => $penalty,
                'late_count' => $lateCount,
                'label_detail' => $countLabel,
                'present_count' => $presentCount,
                'type' => $enrollment?->isKelas() ? 'kelas' : 'privat',
            ];
        })->values();

        return [
            'rows' => $rows,
            'grand_total' => $rows->sum('total'),
            'total_penalty' => $rows->sum('penalty'),
            'final_total' => $rows->sum('total') - $rows->sum('penalty'),
        ];
    }
}