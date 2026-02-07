<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $gross = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->sum(DB::raw('attendance_student.total_present * enrollments.parent_rate'));

        $teacherCost = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->sum(DB::raw('enrollment_attendances.total_lessons * enrollments.teacher_rate'));

        $net = $gross - $teacherCost;

        $activeStudents = Student::query()
            ->where('status', 'active')
            ->count();

        $activeTeachers = Teacher::query()
            ->where('status', 'active')
            ->count();

        $needsFix = MonthlyAttendance::query()
            ->where('status_validation', 'revisi')
            ->where('month', $month)
            ->where('year', $year)
            ->count();

        $chart = $this->buildMonthlyChart();

        return view('admin.finance.dashboard', [
            'month' => $month,
            'year' => $year,
            'gross' => $gross,
            'teacherCost' => $teacherCost,
            'net' => $net,
            'activeStudents' => $activeStudents,
            'activeTeachers' => $activeTeachers,
            'needsFix' => $needsFix,
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

    private function buildMonthlyChart(): array
    {
        $periods = collect(range(5, 0))
            ->map(fn (int $offset) => Carbon::now()->subMonths($offset)->startOfMonth());

        $conditions = $periods
            ->map(fn (Carbon $date) => ['month' => $date->month, 'year' => $date->year]);

        $grossQuery = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(attendance_student.total_present * enrollments.parent_rate) as gross')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(function ($sub) use ($condition) {
                        $sub->where('enrollment_attendances.month', $condition['month'])
                            ->where('enrollment_attendances.year', $condition['year']);
                    });
                }
            })
            ->groupBy('enrollment_attendances.year', 'enrollment_attendances.month')
            ->get()
            ->keyBy(function ($row) {
                return sprintf('%04d-%02d', $row->year, $row->month);
            });

        $costQuery = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(enrollment_attendances.total_lessons * enrollments.teacher_rate) as cost')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(function ($sub) use ($condition) {
                        $sub->where('enrollment_attendances.month', $condition['month'])
                            ->where('enrollment_attendances.year', $condition['year']);
                    });
                }
            })
            ->groupBy('enrollment_attendances.year', 'enrollment_attendances.month')
            ->get()
            ->keyBy(function ($row) {
                return sprintf('%04d-%02d', $row->year, $row->month);
            });

        $byPeriod = $grossQuery;
        $costByPeriod = $costQuery;

        $labels = [];
        $grossSeries = [];
        $costSeries = [];
        $netSeries = [];

        foreach ($periods as $period) {
            $key = $period->format('Y-m');
            $labels[] = $period->format('M Y');
            $grossValue = (float) ($byPeriod[$key]->gross ?? 0);
            $costValue = (float) ($costByPeriod[$key]->cost ?? 0);
            $grossSeries[] = $grossValue;
            $costSeries[] = $costValue;
            $netSeries[] = $grossValue - $costValue;
        }

        return [
            'labels' => $labels,
            'gross' => $grossSeries,
            'cost' => $costSeries,
            'net' => $netSeries,
        ];
    }
}
