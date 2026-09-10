<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use App\Services\AttendanceFineService;
use App\Services\CalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalaryProjectionController extends Controller
{
    public function __construct(
        private AttendanceFineService $fineService,
        private CalculationService $calculationService,
    ) {}

    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $teacher = Teacher::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        $attendances = MonthlyAttendance::with(['enrollment.program', 'enrollment.teacher', 'students'])
            ->when($teacher, function ($query) use ($teacher) {
                $query->where(function ($q) use ($teacher) {
                    $q->whereHas('enrollment', fn ($sub) => $sub->where('teacher_id', $teacher->id));
                    $q->orWhere('session_teacher_id', $teacher->id);
                });
            })
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('enrollment_id')
            ->orderByDesc('lesson_date')
            ->get();

        $result = $this->calculationService->calculateTeacherSalary($teacher?->id, $month, $year, $attendances);

        $groupedRows = collect($result['rows'])->map(function (array $row) {
            $presentCount = $row['present_count'] ?? 0;
            $isPrivat = ($row['type'] ?? '') === 'privat';
            $isKelas = ($row['type'] ?? '') === 'kelas';
            $countLabel = $isPrivat
                ? ($presentCount > 1 ? sprintf('grup %d orang', $presentCount) : '')
                : ($isKelas ? '1 siswa (kelas)' : 'kelas tanpa murid');
            $sessionCount = $row['count'];
            $ratePerSession = $sessionCount > 0 ? (int) round($row['total'] / $sessionCount) : 0;

            return [
                'enrollment_id' => $row['enrollment_id'],
                'enrollment' => $row['enrollment'],
                'program' => $row['program'],
                'students' => $row['_students'] ?? collect(),
                'session_count' => $sessionCount,
                'late_count' => $row['late_count'],
                'total_rate' => $row['total'],
                'rate_per_session' => $ratePerSession,
                'total_penalty' => $row['penalty'],
                'total_salary' => $row['total'] - $row['penalty'],
                'overall_status' => $row['overall_status'],
                'payment_status' => $row['payment_status'],
                'type' => $row['type'],
                'present_count' => $presentCount,
                'label_detail' => $countLabel,
            ];
        });

        $totals = $this->buildTotals($attendances);
        $chart = $this->buildMonthlyChart($teacher?->id);

        return view('guru.salary-projection.index', [
            'month' => $month,
            'year' => $year,
            'teacher' => $teacher,
            'groupedRows' => $groupedRows,
            'totals' => $totals,
            'chart' => $chart,
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
    }

    private function buildTotals($attendances): array
    {
        $rows = $attendances->map(function (MonthlyAttendance $attendance) {
            // Use snapshot rate from attendance record (captured at time of validation), not current enrollment rate
            $rate = (int) ($attendance->teacher_rate ?? $attendance->enrollment?->teacher_rate ?? 0);
            $isLate = $attendance->status_validation === 'terlambat';
            $penalty = $this->calculationService->getLatePenaltyFrozen($attendance, $rate, $isLate ? 1 : 0);
            $total = $rate - $penalty;

            return [
                'status' => 'validated',
                'total' => $total,
                'penalty' => $penalty,
                'rate' => $rate,
                'is_late' => $isLate,
            ];
        });

        $latePenalty = $rows->sum('penalty');

        return [
            'validated' => (int) $rows->sum('total'),
            'pending' => 0,
            'rejected' => 0,
            'late_penalty' => $latePenalty,
            'grand' => (int) $rows->sum('total'),
        ];
    }

    private function buildMonthlyChart(?int $teacherId): array
    {
        $periods = collect(range(5, 0))
            ->map(fn (int $offset) => Carbon::now()->subMonths($offset)->startOfMonth());

        $labels = $periods->map(fn (Carbon $date) => $date->format('M Y'))->values()->all();

        if (! $teacherId) {
            return [
                'labels' => $labels,
                'totals' => array_fill(0, count($labels), 0),
            ];
        }

        $conditions = $periods
            ->map(fn (Carbon $date) => ['month' => $date->month, 'year' => $date->year]);

        $query = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month,
                SUM(CASE WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate ELSE 0 END) as terima_total,
                COUNT(CASE WHEN enrollment_attendances.status_validation = ? THEN 1 END) as terlambat_count,
                SUM(CASE WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate ELSE 0 END) as terlambat_gross', ['terima', 'terlambat', 'terlambat'])
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where(function ($builder) use ($teacherId) {
                $builder->where('enrollments.teacher_id', $teacherId)
                    ->orWhere('enrollment_attendances.session_teacher_id', $teacherId);
            })
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(function ($sub) use ($condition) {
                        $sub->where('enrollment_attendances.month', $condition['month'])
                            ->where('enrollment_attendances.year', $condition['year']);
                    });
                }
            })
            ->groupBy('enrollment_attendances.year', 'enrollment_attendances.month')
            ->get();

        $byPeriod = $query->keyBy(function ($row) {
            return sprintf('%04d-%02d', $row->year, $row->month);
        });

        $totals = [];

        foreach ($periods as $period) {
            $key = $period->format('Y-m');
            $row = $byPeriod[$key] ?? null;
            if (! $row) {
                $totals[] = 0.0;
                continue;
            }
            $terima = (float) ($row->terima_total ?? 0);
            $terlambatGross = (float) ($row->terlambat_gross ?? 0);
            $lateCount = (int) ($row->terlambat_count ?? 0);
            $avgRate = $lateCount > 0 ? $terlambatGross / $lateCount : 0;
            $penalty = $this->fineService->getLatePenaltyAmount($avgRate, $lateCount);
            $totals[] = $terima + $terlambatGross - $penalty;
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
        ];
    }
}
