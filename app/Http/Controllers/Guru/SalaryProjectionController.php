<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalaryProjectionController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $teacher = Teacher::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        $attendances = MonthlyAttendance::with(['enrollment.program', 'enrollment.teacher', 'students'])
            ->when($teacher, fn ($query) => $query->whereHas('enrollment', fn ($sub) => $sub->where('teacher_id', $teacher->id)))
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $totals = $this->buildTotals($attendances);
        $chart = $this->buildMonthlyChart($teacher?->id);

        return view('guru.salary-projection.index', [
            'month' => $month,
            'year' => $year,
            'teacher' => $teacher,
            'attendances' => $attendances,
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
            $rate = $attendance->enrollment?->teacher_rate ?? 0;
            $total = $attendance->total_lessons * $rate;

            $status = match ($attendance->status_validation) {
                'valid' => 'validated',
                'revisi' => 'needs_fix',
                default => 'pending',
            };

            return [
                'status' => $status,
                'total' => $total,
            ];
        });

        return [
            'validated' => (int) $rows->where('status', 'validated')->sum('total'),
            'pending' => (int) $rows->where('status', 'pending')->sum('total'),
            'needs_fix' => (int) $rows->where('status', 'needs_fix')->sum('total'),
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
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(enrollment_attendances.total_lessons * enrollments.teacher_rate) as total')
            ->where('enrollments.teacher_id', $teacherId)
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
            $totals[] = (float) ($byPeriod[$key]->total ?? 0);
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
        ];
    }
}
