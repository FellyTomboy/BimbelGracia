<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentStudentDiscount;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            $presentCount = (int) ($first->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count());
            $baseRate = (int) ($first->parent_rate ?? 0);
            // parent_rate is the GROUP total; divide by present_count for per-student rate
            $perStudentRate = $presentCount > 0 ? (int) round($baseRate / $presentCount) : $baseRate;
            $totalCount = $group->sum(function (MonthlyAttendance $attendance) use ($student) {
                $s = $attendance->students->firstWhere('id', $student->id);
                return (int) ($s?->pivot?->total_present ?? 0);
            });
            $totalSessions = $group->count();
            $studentTotalPresent = $totalCount;
            // Penalty: check against ALL sessions this student had in this enrollment this month
            $allStudentSessions = $attendances->filter(fn ($a) => $a->enrollment_id === $first->enrollment_id && $a->students->contains('id', $student->id));
            $totalSessionsThisMonth = $allStudentSessions->count();
            $penalty = $this->resolveAttendancePenalty($enrollment, $first, $totalSessionsThisMonth, $studentTotalPresent);

            // Penalty adds per-session amount when student attended, applied to per-student rate
            $hasPenalty = $penalty > 0;
            $adjustedRate = $perStudentRate + ($hasPenalty
                ? $this->getAttendancePenaltyPerSessionFrozen($first, $perStudentRate)
                : 0);
            $inflatedSubtotal = $adjustedRate * $totalCount;

            // Discount applies to the inflated subtotal (rate after penalty × count)
            $discount = $this->resolveDiscountForBilling($enrollment, $student->id, $month, $year, $inflatedSubtotal);

            $groupNote = $presentCount > 1 ? sprintf('(grup %d orang)', $presentCount) : '';

            $rows->push([
                'enrollment_id' => $first->enrollment_id,
                'program' => $enrollment?->program?->name ?? '-',
                'teacher' => $enrollment?->teacher?->display_name
                    ?? $first->sessionTeacher?->display_name
                    ?? '-',
                'count' => $totalCount,
                'rate' => $perStudentRate,
                'subtotal' => $inflatedSubtotal,
                'discount' => $discount,
                'penalty' => $penalty,
                'has_penalty' => $hasPenalty,
                'total' => $discount['total'],
                'detail' => $groupNote,
                'present_count' => $presentCount,
                'type' => 'privat',
                'attendance_ids' => $group->pluck('id')->values()->toArray(),
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
                'has_penalty' => false,
                'total' => $discount['total'],
                'detail' => sprintf('Hadir %d/%d (%d%%)', $studentTotalPresent, $agreedSessions, (int) $attendancePercent),
                'present_count' => 1,
                'type' => 'kelas',
                'attendance_ids' => $group->pluck('id')->values()->toArray(),
            ]);
        }

        $rows = $rows->values();
        $grossTotal = $rows->sum(fn (array $r) => $r['subtotal']);

        return [
            'rows' => $rows,
            'gross_total' => $grossTotal,
            'grand_total' => $rows->sum('total'),
            'total_discount' => $rows->sum(fn (array $r) => $r['discount']['amount'] ?? 0),
            'total_penalty' => $rows->sum('penalty'),
            'net_total' => $grossTotal - $rows->sum(fn (array $r) => $r['discount']['amount'] ?? 0),
        ];
    }

    private function resolveAttendancePenalty(?Enrollment $enrollment, MonthlyAttendance $first, int $totalSessions, int $studentTotalPresent): int
    {
        if (! $this->fineService->isAttendancePenaltyEnabled()) {
            return 0;
        }
        if (! $enrollment || ! $enrollment->hasAttendancePenalty($totalSessions, $studentTotalPresent)) {
            return 0;
        }
        // Use frozen fine fields from the attendance record (fallback to current settings for old records)
        $type = $first->attendance_penalty_type ?? $this->fineService->getAttendancePenaltyType();
        $value = $first->attendance_penalty_value ?? $this->fineService->getAttendancePenaltyValue();
        $penaltyPerSession = $type === 'percent'
            ? (int) ($first->parent_rate * $value / 100)
            : (int) $value;
        return $studentTotalPresent * $penaltyPerSession;
    }

    private function getAttendancePenaltyPerSessionFrozen(MonthlyAttendance $first, int $perStudentRate): int
    {
        $type = $first->attendance_penalty_type ?? $this->fineService->getAttendancePenaltyType();
        $value = $first->attendance_penalty_value ?? $this->fineService->getAttendancePenaltyValue();
        if ($type === 'percent') {
            return (int) ($perStudentRate * $value / 100);
        }
        return (int) $value;
    }

    private function getLatePenaltyAmountFrozen(MonthlyAttendance $first, float $rate, int $lateCount): int
    {
        if ($lateCount <= 0) {
            return 0;
        }
        // Use frozen late penalty fields from the attendance record (fallback to current settings)
        $type = $first->late_penalty_type ?? $this->fineService->getLatePenaltyType();
        $value = $first->late_penalty_value ?? $this->fineService->getLatePenaltyValue();
        if ($type === 'percent') {
            return (int) ($lateCount * $rate * $value / 100);
        }
        return (int) ($lateCount * $value);
    }

    private function resolveDiscountForBilling(?Enrollment $enrollment, int $studentId, int $month, int $year, int $baseTotal): array
    {
        $empty = ['amount' => 0, 'type' => null, 'label' => null, 'value' => null, 'base_total' => $baseTotal, 'total' => $baseTotal];
        if (! $enrollment) {
            return $empty;
        }
        $record = EnrollmentStudentDiscount::where('enrollment_id', $enrollment->id)
            ->where('student_id', $studentId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
        if (! $record) {
            return $empty;
        }
        $type = strtolower((string) ($record->discount_type ?? ''));
        $value = (int) ($record->discount_value ?? 0);
        $amount = 0;
        $label = null;

        if ($type === 'percent' || $type === 'percentage') {
            $percent = max(0, min(100, $value));
            $amount = (int) round($baseTotal * $percent / 100);
            $label = sprintf('%d%%', $percent);
        } elseif ($type === 'amount') {
            $amount = min($value, $baseTotal);
            $label = sprintf('Rp %s', number_format($value));
        } elseif ($type === 'final') {
            $finalTotal = max(0, min($value, $baseTotal));
            $amount = max(0, $baseTotal - $finalTotal);
            $label = sprintf('Rp %s', number_format($finalTotal));
        }

        return [
            'amount' => $amount,
            'type' => $type ?: null,
            'label' => $label,
            'value' => $value,
            'base_total' => $baseTotal,
            'total' => max(0, $baseTotal - $amount),
        ];
    }

    /**
     * Calculate billing totals for a single attendance record across all its students.
     * Used for the "all students" view where each row = one attendance record.
     * Returns: [total, present_sum, parent_rate]
     */
    public function calculateAttendanceBilling(MonthlyAttendance $attendance): array
    {
        $rate = (int) ($attendance->parent_rate ?? 0);
        $presentSum = $attendance->students->sum(fn ($s) => (int) ($s->pivot?->total_present ?? 0));
        $total = $presentSum * $rate;

        return [
            'total' => $total,
            'present_sum' => $presentSum,
            'parent_rate' => $rate,
        ];
    }

    /**
     * Calculate teacher salary for a teacher in a given month.
     * Returns grouped rows with rate, count, subtotal, penalty.
     */
    public function calculateTeacherSalary(int $teacherId, int $month, int $year, Collection $attendances): array
    {
        $rows = collect();

        // PRIVATE: one row per enrollment-group
        $privatAttendances = $attendances
            ->filter(fn (MonthlyAttendance $attendance) => $attendance->enrollment?->isPrivat())
            ->filter(fn (MonthlyAttendance $attendance) =>
                (int) ($attendance->enrollment->teacher_id ?? 0) === $teacherId
            )
            ->filter(fn (MonthlyAttendance $attendance) =>
                $attendance->students->sum(fn ($s) => (int) ($s->pivot->total_present ?? 0)) > 0
            );

        $privatGrouped = $privatAttendances->groupBy(function (MonthlyAttendance $attendance) {
            $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
            $rate = (int) ($attendance->teacher_rate ?? $attendance->enrollment?->getTeacherRateForCount($presentCount) ?? 0);
            return $attendance->enrollment_id . '-' . $rate . '-' . $presentCount;
        });

        foreach ($privatGrouped as $group) {
            $first = $group->first();
            $enrollment = $first->enrollment;
            $presentStudents = $group->flatMap(fn ($a) => $a->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0))->unique('id')->values();
            $studentNames = $presentStudents->map(fn ($s) => $s->display_name)->implode(' & ');
            $presentCount = $presentStudents->count();
            // Sessions: count of individual attendance records (one per student per session)
            $sessionCount = $group->count();
            $rate = (int) ($first->teacher_rate ?? 0);
            $lateCount = $group->filter(fn ($a) => $a->status_validation === 'terlambat')->count();
            // Teacher salary = sessions × rate (rate is per-session, not per-student)
            $grossTotal = $sessionCount * $rate;
            $penalty = $this->fineService->isLatePenaltyEnabled()
                ? $this->getLatePenaltyAmountFrozen($first, $rate, $lateCount)
                : 0;
            $labelDetail = $presentCount > 1 ? sprintf('grup %d orang', $presentCount) : '';
            $lateStatuses = $group->pluck('status_validation')->unique();
            $overallStatus = $lateStatuses->every(fn ($s) => $s === 'terima') ? 'terima' : 'terlambat';

            $rows->push([
                'enrollment_id' => $first->enrollment_id,
                'enrollment' => $enrollment,
                'program' => $enrollment?->program,
                'student' => $studentNames ?: '-',
                'student_label' => ($studentNames ?: '-') . ($presentCount > 1 ? ' ' . sprintf('(grup %d orang)', $presentCount) : ''),
                '_students' => $presentStudents,
                'count' => $sessionCount,
                'rate' => $rate,
                'total' => $grossTotal,
                'penalty' => $penalty,
                'late_count' => $lateCount,
                'overall_status' => $overallStatus,
                'payment_status' => $first->teacher_payment_status,
                'label_detail' => $labelDetail,
                'present_count' => $presentCount,
                'type' => 'privat',
                'attendance_ids' => $group->pluck('id')->values()->toArray(),
            ]);
        }

        // KELAS: one row per program (all sessions regardless of enrollment, per teacher)
        // Includes: regular kelas enrollments + orphan records (enrollment_id=null but has classSession with kelas program)
        // Orphan records on unique classSessions represent real confirmed sessions — include them.
        // Regular records: only include if students attended (present > 0).
        // Orphan records: always include (teacher confirmed the session regardless of student enrollment).
        $kelasAttendances = $attendances
            ->filter(fn (MonthlyAttendance $attendance) =>
                // Regular kelas enrollment with students present
                ($attendance->enrollment?->isKelas()
                    && (int) ($attendance->session_teacher_id ?? 0) === $teacherId
                    && $attendance->students->sum(fn ($s) => (int) ($s->pivot->total_present ?? 0)) > 0)
                // Orphan kelas session: no enrollment, has classSession, program is kelas
                || ($attendance->enrollment_id === null
                    && $attendance->classSession !== null
                    && $attendance->classSession?->program?->isKelas()
                    && (int) ($attendance->session_teacher_id ?? 0) === $teacherId)
            );

        // Group by program name
        $kelasGrouped = $kelasAttendances->groupBy(fn (MonthlyAttendance $a) => $a->classSession?->program?->name ?? '-');

        foreach ($kelasGrouped as $programName => $group) {
            $first = $group->first();

            // Count unique classSessions: each classSession = one teacher session.
            // Regular records with the same classSession (multiple students) count as 1.
            // Orphan records each represent one session.
            $regularInGroup = $group->filter(fn (MonthlyAttendance $a) => $a->enrollment_id !== null);
            $orphanInGroup = $group->filter(fn (MonthlyAttendance $a) => $a->enrollment_id === null);
            $regularClassSessionCount = $regularInGroup->pluck('class_session_id')->unique()->count();
            $count = $regularClassSessionCount + $orphanInGroup->count();

            $rate = (int) ($first->teacher_rate ?? 0);
            $lateCount = $group->filter(fn ($a) => $a->status_validation === 'terlambat')->count();
            $penalty = 0; // Denda keterlambatan tidak berlaku untuk program kelas — admin yang input presensi
            $lateStatuses = $group->pluck('status_validation')->unique();
            $overallStatus = $lateStatuses->every(fn ($s) => $s === 'terima') ? 'terima' : 'terlambat';

            $rows->push([
                'enrollment_id' => null,
                'enrollment' => null,
                'program' => $first->classSession?->program,
                'student' => '-',
                '_students' => collect(),
                'count' => $count,
                'rate' => $rate,
                'total' => $count * $rate,
                'penalty' => $penalty,
                'late_count' => $lateCount,
                'overall_status' => $overallStatus,
                'payment_status' => $first->teacher_payment_status,
                'label_detail' => sprintf('%dx %s @ Rp %s = Rp %s', $count, $programName, number_format($rate), number_format($count * $rate)),
                'present_count' => 1,
                'type' => 'kelas',
                'attendance_ids' => $group->pluck('id')->values()->toArray(),
            ]);
        }

        // KELAS TANPA MURID: records with no enrollment AND no classSession —
        // truly orphan records (confirmed teacher attendance, no link at all)
        $teacherOnlyAttendances = $attendances
            ->filter(fn (MonthlyAttendance $attendance) =>
                $attendance->enrollment_id === null
                && $attendance->classSession === null
                && (int) ($attendance->session_teacher_id ?? 0) === $teacherId
            );

        $teacherOnlyGrouped = $teacherOnlyAttendances
            ->groupBy(fn (MonthlyAttendance $a) => ($a->classSession?->program?->name ?? '-') . '|' . (int) ($a->teacher_rate ?? 0));

        foreach ($teacherOnlyGrouped as $group) {
            $first = $group->first();
            $programName = $first->classSession?->program?->name ?? '-';
            $rate = (int) ($first->teacher_rate ?? 0);
            $count = $group->count();
            $lateCount = $group->filter(fn (MonthlyAttendance $a) => $a->status_validation === 'terlambat')->count();
            $penalty = 0; // Denda keterlambatan tidak berlaku untuk program kelas — admin yang input presensi
            $lateStatuses = $group->pluck('status_validation')->unique();
            $overallStatus = $lateStatuses->every(fn ($s) => $s === 'terima') ? 'terima' : 'terlambat';

            $rows->push([
                'enrollment_id' => null,
                'enrollment' => null,
                'program' => $first->classSession?->program,
                'student' => $programName,
                '_students' => collect(),
                'count' => $count,
                'rate' => $rate,
                'total' => $count * $rate,
                'penalty' => $penalty,
                'late_count' => $lateCount,
                'overall_status' => $overallStatus,
                'payment_status' => $first->teacher_payment_status,
                'label_detail' => $count . 'x ' . $programName . ' @ Rp ' . number_format($rate) . ' = Rp ' . number_format($count * $rate),
                'present_count' => 0,
                'type' => 'kelas_tanpa_murid',
                'attendance_ids' => $group->pluck('id')->values()->toArray(),
            ]);
        }

        return [
            'rows' => $rows->values(),
            'gross_total' => $rows->sum('total'),
            'grand_total' => $rows->sum('total') - $rows->sum('penalty'),
            'total_penalty' => $rows->sum('penalty'),
            'final_total' => $rows->sum('total') - $rows->sum('penalty'),
        ];
    }

    /**
     * Public version of the frozen late penalty calculation for use by other controllers.
     * Rate is per-session (not per-student).
     */
    public function getLatePenaltyFrozen(MonthlyAttendance $attendance, float $rate, int $lateCount): int
    {
        if ($lateCount <= 0) {
            return 0;
        }
        $type = $attendance->late_penalty_type ?? $this->fineService->getLatePenaltyType();
        $value = $attendance->late_penalty_value ?? $this->fineService->getLatePenaltyValue();
        if ($type === 'percent') {
            return (int) ($lateCount * $rate * $value / 100);
        }

        return (int) ($lateCount * $value);
    }

    /**
     * Get frozen penalty info from an attendance record for PDF display labels.
     */
    public function getFrozenPenaltyInfo(MonthlyAttendance $attendance): array
    {
        return [
            'attendance_penalty_type' => $attendance->attendance_penalty_type
                ?? $this->fineService->getAttendancePenaltyType(),
            'attendance_penalty_value' => $attendance->attendance_penalty_value
                ?? $this->fineService->getAttendancePenaltyValue(),
            'late_penalty_type' => $attendance->late_penalty_type
                ?? $this->fineService->getLatePenaltyType(),
            'late_penalty_value' => $attendance->late_penalty_value
                ?? $this->fineService->getLatePenaltyValue(),
        ];
    }

    /**
     * Calculate teacher salary for a single attendance record (all students combined).
     * Returns { total, teacher_rate, session_count }.
     */
    public function calculateAttendanceSalary(MonthlyAttendance $attendance): array
    {
        $rate = (int) ($attendance->teacher_rate ?? 0);
        $sessionCount = $attendance->students->count() > 0
            ? $attendance->students->sum(fn ($s) => (int) ($s->pivot?->total_present ?? 0))
            : 1;
        $total = $rate * $sessionCount;
        $lateCount = $attendance->status_validation === 'terlambat' ? 1 : 0;
        $penalty = $this->getLatePenaltyFrozen($attendance, $rate, $lateCount);

        return [
            'total' => $total,
            'penalty' => $penalty,
            'teacher_rate' => $rate,
            'session_count' => $sessionCount,
        ];
    }

    /**
     * Calculate billing for all students present in a collection of attendances for one month.
     * Returns [student_id => ['student' => Student, 'billing' => calculateStudentBilling result]].
     */
    public function calculateAllStudentBillings(int $month, int $year, Collection $attendances): array
    {
        $byStudent = $attendances
            ->filter(fn ($a) => $a->relationLoaded('students') && $a->students->isNotEmpty())
            ->flatMap(fn ($a) => $a->students)
            ->unique('id')
            ->values();

        $results = [];
        foreach ($byStudent as $student) {
            $studentAttendances = $attendances->filter(fn ($a) =>
                $a->students->contains('id', $student->id)
                && ($a->students->firstWhere('id', $student->id)->pivot?->total_present ?? 0) > 0
            );
            if ($studentAttendances->isEmpty()) {
                continue;
            }
            $results[$student->id] = [
                'student' => $student,
                'billing' => $this->calculateStudentBilling($student, $month, $year, $studentAttendances),
            ];
        }

        return $results;
    }

    /**
     * Calculate salary for all teachers present in a collection of attendances for one month.
     * Returns [teacher_id => ['teacher_id', 'billing' => calculateTeacherSalary result]].
     */
    public function calculateAllTeacherSalaries(int $month, int $year, Collection $attendances): array
    {
        $teacherIds = $attendances
            ->map(fn ($a) => [$a->enrollment?->teacher_id, $a->session_teacher_id])
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $results = [];
        foreach ($teacherIds as $teacherId) {
            $teacherAttendances = $attendances->filter(fn ($a) =>
                (int) ($a->enrollment?->teacher_id ?? 0) === $teacherId
                || (int) ($a->session_teacher_id ?? 0) === $teacherId
            );
            if ($teacherAttendances->isEmpty()) {
                continue;
            }
            $results[$teacherId] = [
                'teacher_id' => $teacherId,
                'billing' => $this->calculateTeacherSalary($teacherId, $month, $year, $teacherAttendances),
            ];
        }

        return $results;
    }
}