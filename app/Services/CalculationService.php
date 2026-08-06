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
    /**
     * Calculate parent billing for a student in a given month.
     * Returns grouped rows with rate, count, subtotal, discount, penalty.
     */
    public function calculateStudentBilling(Student $student, int $month, int $year, Collection $attendances): array
    {
        // Filter out attendances where the student has 0 present
        $attendances = $attendances->filter(function (MonthlyAttendance $attendance) use ($student) {
            $s = $attendance->students->firstWhere('id', $student->id);
            return ($s?->pivot?->total_present ?? 0) > 0;
        });

        // Group by (enrollment_id, rate, present_count) because rate differs per student count
        $grouped = $attendances->groupBy(function (MonthlyAttendance $attendance) use ($student) {
            $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($attendance->parent_rate ?? $attendance->enrollment?->getParentRateForCount($presentCount) ?? 0);
            return $attendance->enrollment_id . '-' . $rate . '-' . $presentCount;
        });

        $rows = $grouped->map(function (Collection $group) use ($student, $month, $year) {
            $first = $group->first();
            $enrollment = $first->enrollment;
            $presentCount = $group->first()->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($first->parent_rate ?? 0);
            $totalCount = $group->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $subtotal = $totalCount * $rate;

            // Check attendance penalty for this enrollment
            $totalSessions = $group->count();
            $studentTotalPresent = $group->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $penalty = 0;
            if ($enrollment && $enrollment->hasAttendancePenalty($totalSessions, $studentTotalPresent)) {
                $penalty = $totalCount * 5000; // Rp 5.000 extra per session
            }

            // Get discount for this enrollment+student+month
            $discount = 0;
            if ($enrollment) {
                $discountRecord = EnrollmentStudentDiscount::where('enrollment_id', $enrollment->id)
                    ->where('student_id', $student->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();
                if ($discountRecord) {
                    $type = strtolower($discountRecord->discount_type ?? '');
                    $value = (int) ($discountRecord->discount_value ?? 0);
                    if ($type === 'percent' || $type === 'percentage') {
                        $percent = max(0, min(100, $value));
                        $discount = (int) round($subtotal * $percent / 100);
                    } elseif ($type === 'amount') {
                        $discount = min($value, $subtotal);
                    } elseif ($type === 'final') {
                        $finalTotal = max(0, min($value, $subtotal));
                        $discount = $subtotal - $finalTotal;
                    }
                }
            }

            $detailLabel = '';
            if ($presentCount > 1) {
                $detailLabel = ' (grup ' . $presentCount . ' siswa)';
            }

            return [
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
            ];
        })->values();

        return [
            'rows' => $rows,
            'grand_total' => $rows->sum('total'),
            'total_discount' => $rows->sum('discount'),
            'total_penalty' => $rows->sum('penalty'),
        ];
    }

    /**
     * Calculate teacher salary for a teacher in a given month.
     * Returns grouped rows with rate, count, subtotal, penalty.
     */
    public function calculateTeacherSalary(int $teacherId, int $month, int $year, Collection $attendances): array
    {
        // Filter out attendances where no students are present
        $attendances = $attendances->filter(function (MonthlyAttendance $attendance) {
            return $attendance->students->sum(fn ($s) => (int) ($s->pivot->total_present ?? 0)) > 0;
        });

        // Group by (enrollment_id, rate, present_count)
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
            $studentNames = $group->flatMap(fn ($a) => $a->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->map->name)->unique()->implode(', ');
            $totalCount = $group->sum(fn ($a) => $a->students->sum(fn ($s) => (int) ($s->pivot->total_present ?? 0)));
            $lateCount = $group->filter(fn ($a) => $a->status_validation === 'terlambat')->count();
            $grossTotal = $totalCount * $rate;
            $penalty = (int) ($lateCount * $rate * 0.1); // 10% penalty per late session

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