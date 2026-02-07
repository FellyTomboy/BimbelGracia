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

        $baseQuery = DB::table('monthly_attendances')
            ->join('lessons', 'monthly_attendances.lesson_id', '=', 'lessons.id')
            ->where('monthly_attendances.status', 'validated')
            ->where('monthly_attendances.month', $month)
            ->where('monthly_attendances.year', $year);

        $gross = (clone $baseQuery)
            ->sum(DB::raw('monthly_attendances.total_lessons * lessons.parent_rate'));

        $teacherCost = (clone $baseQuery)
            ->sum(DB::raw('monthly_attendances.total_lessons * lessons.teacher_rate'));

        $net = $gross - $teacherCost;

        $activeStudents = Student::query()
            ->where('status', 'active')
            ->count();

        $activeTeachers = Teacher::query()
            ->where('status', 'active')
            ->count();

        $needsFix = MonthlyAttendance::query()
            ->where('status', 'needs_fix')
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

        $query = DB::table('monthly_attendances')
            ->join('lessons', 'monthly_attendances.lesson_id', '=', 'lessons.id')
            ->selectRaw('monthly_attendances.year, monthly_attendances.month, SUM(monthly_attendances.total_lessons * lessons.parent_rate) as gross, SUM(monthly_attendances.total_lessons * lessons.teacher_rate) as cost')
            ->where('monthly_attendances.status', 'validated')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(function ($sub) use ($condition) {
                        $sub->where('monthly_attendances.month', $condition['month'])
                            ->where('monthly_attendances.year', $condition['year']);
                    });
                }
            })
            ->groupBy('monthly_attendances.year', 'monthly_attendances.month')
            ->get();

        $byPeriod = $query->keyBy(function ($row) {
            return sprintf('%04d-%02d', $row->year, $row->month);
        });

        $labels = [];
        $grossSeries = [];
        $costSeries = [];
        $netSeries = [];

        foreach ($periods as $period) {
            $key = $period->format('Y-m');
            $labels[] = $period->format('M Y');
            $grossValue = (float) ($byPeriod[$key]->gross ?? 0);
            $costValue = (float) ($byPeriod[$key]->cost ?? 0);
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
