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
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $privatGross = DB::table('enrollment_attendances')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->sum(DB::raw('attendance_student.total_present * enrollment_attendances.parent_rate'));

        $privatTeacherCost = DB::table('enrollment_attendances')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->sum(DB::raw('enrollment_attendances.total_lessons * enrollment_attendances.teacher_rate'));

        $classGross = DB::table('class_student_sessions')
            ->join('class_student_session_student', 'class_student_sessions.id', '=', 'class_student_session_student.class_student_session_id')
            ->join('class_students', 'class_student_session_student.class_student_id', '=', 'class_students.id')
            ->whereMonth('class_student_sessions.session_date', $month)
            ->whereYear('class_student_sessions.session_date', $year)
            ->sum(DB::raw('class_students.rate_per_meeting'));

        $gross = $privatGross + $classGross;
        $teacherCost = $privatTeacherCost;

        $net = $gross - $teacherCost;

        $activeStudents = Student::query()
            ->where('status', 'active')
            ->count();

        $activeClassStudents = DB::table('class_students')
            ->whereNull('deleted_at')
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

        $mode = $this->resolveMode($request);
        [$rangeStart, $rangeEnd] = $this->resolveRange($request, $mode);

        $chartFinance = $this->buildFinanceChartByRange($rangeStart, $rangeEnd, $mode);
        $chartStudents = $this->buildStudentsChartByRange($rangeStart, $rangeEnd, $mode);
        $chartTeachers = $this->buildTeachersChartByRange($rangeStart, $rangeEnd, $mode);

        return view('admin.finance.dashboard', [
            'month' => $month,
            'year' => $year,
            'gross' => $gross,
            'teacherCost' => $teacherCost,
            'net' => $net,
            'activeStudents' => $activeStudents,
            'activeClassStudents' => $activeClassStudents,
            'activeTeachers' => $activeTeachers,
            'needsFix' => $needsFix,

            'mode' => $mode,
            'rangeStart' => $rangeStart->format('Y-m'),
            'rangeEnd' => $rangeEnd->format('Y-m'),

            'chartFinance' => $chartFinance,
            'chartStudents' => $chartStudents,
            'chartTeachers' => $chartTeachers,
        ]);
    }

    private function resolveMode(Request $request): string
    {
        $mode = strtolower((string) $request->input('mode', 'monthly'));
        return $mode === 'yearly' ? 'yearly' : 'monthly';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request, string $mode): array
    {
        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);

        if ($mode === 'yearly') {
            $startYear = (int) $request->input('range_start', $now->year - 4);
            $endYear = (int) $request->input('range_end', $now->year);

            $startYear = max(2020, min(2100, $startYear));
            $endYear = max(2020, min(2100, $endYear));

            if ($endYear < $startYear) {
                [$startYear, $endYear] = [$endYear, $startYear];
            }

            return [$now->copy()->setYear($startYear)->startOfYear(), $now->copy()->setYear($endYear)->endOfYear()];
        }

        // Default rentang diambil dari data terakhir yang tersedia agar selalu mengikuti "bulan berjalan" dari sisi bisnis
        $defaultEnd = DB::table('enrollment_attendances')
            ->selectRaw('MAX(year) as max_year, MAX(month) as max_month')
            ->first();

        $defaultEndYM = null;
        if ($defaultEnd && !empty($defaultEnd->max_year) && !empty($defaultEnd->max_month)) {
            // Perhatian: MAX(month) tanpa korelasi dengan MAX(year) tidak ideal.
            // Jadi kita ambil latest berdasarkan (year, month) dengan sorting.
            $latest = DB::table('enrollment_attendances')
                ->select(['year', 'month'])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->first();

            if ($latest) {
                $defaultEndYM = sprintf('%04d-%02d', (int) $latest->year, (int) $latest->month);
            }
        }

        $endDefaultCarbon = $defaultEndYM
            ? Carbon::createFromFormat('Y-m', $defaultEndYM, config('app.timezone', 'Asia/Jakarta'))
            : $now;

        $startDefaultCarbon = $endDefaultCarbon->copy()->subMonths(4);

        $startYM = (string) $request->input('range_start', $startDefaultCarbon->format('Y-m'));
        $endYM = (string) $request->input('range_end', $endDefaultCarbon->format('Y-m'));

        $start = Carbon::parse($startYM)->startOfMonth();
        $end = Carbon::parse($endYM)->endOfMonth();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfMonth(), $start->copy()->endOfMonth()];
        }

        return [$start, $end];
    }

    private function buildFinanceChartByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): array
    {
        if ($mode === 'yearly') {
            $years = range($rangeStart->year, $rangeEnd->year);
            $labels = array_map(fn ($y) => (string) $y, $years);

            $privatGross = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
                ->selectRaw('enrollment_attendances.year, SUM(attendance_student.total_present * enrollments.parent_rate) as gross')
                ->where('enrollment_attendances.status_validation', 'valid')
                ->whereBetween('enrollment_attendances.year', [$rangeStart->year, $rangeEnd->year])
                ->groupBy('enrollment_attendances.year')
                ->pluck('gross', 'year');

            $cost = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->selectRaw('enrollment_attendances.year, SUM(enrollment_attendances.total_lessons * enrollments.teacher_rate) as cost')
                ->where('enrollment_attendances.status_validation', 'valid')
                ->whereBetween('enrollment_attendances.year', [$rangeStart->year, $rangeEnd->year])
                ->groupBy('enrollment_attendances.year')
                ->pluck('cost', 'year');

            $classGross = DB::table('class_student_sessions')
                ->join('class_student_session_student', 'class_student_sessions.id', '=', 'class_student_session_student.class_student_session_id')
                ->join('class_students', 'class_student_session_student.class_student_id', '=', 'class_students.id')
                ->selectRaw('YEAR(class_student_sessions.session_date) as year, SUM(class_students.rate_per_meeting) as gross')
                ->whereBetween(DB::raw('YEAR(class_student_sessions.session_date)'), [$rangeStart->year, $rangeEnd->year])
                ->groupBy('year')
                ->pluck('gross', 'year');

            $grossSeries = [];
            $netSeries = [];
            foreach ($years as $y) {
                $g = (float) ($privatGross[$y] ?? 0) + (float) ($classGross[$y] ?? 0);
                $c = (float) ($cost[$y] ?? 0);
                $grossSeries[] = $g;
                $netSeries[] = $g - $c;
            }

            return ['labels' => $labels, 'gross' => $grossSeries, 'net' => $netSeries];
        }

        $periods = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $periods->push($cursor->copy());
            $cursor->addMonthNoOverflow();
        }

        $labels = $periods->map(fn ($d) => $d->format('M Y'))->values()->all();
        $conditions = $periods->map(fn ($d) => ['month' => $d->month, 'year' => $d->year])->values()->all();

        $privatGross = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(attendance_student.total_present * enrollments.parent_rate) as gross')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(fn ($sub) => $sub
                        ->where('enrollment_attendances.month', $condition['month'])
                        ->where('enrollment_attendances.year', $condition['year'])
                    );
                }
            })
            ->groupBy('enrollment_attendances.year', 'enrollment_attendances.month')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month));

        $cost = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(enrollment_attendances.total_lessons * enrollments.teacher_rate) as cost')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(fn ($sub) => $sub
                        ->where('enrollment_attendances.month', $condition['month'])
                        ->where('enrollment_attendances.year', $condition['year'])
                    );
                }
            })
            ->groupBy('enrollment_attendances.year', 'enrollment_attendances.month')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month));

        $classGross = DB::table('class_student_sessions')
            ->join('class_student_session_student', 'class_student_sessions.id', '=', 'class_student_session_student.class_student_session_id')
            ->join('class_students', 'class_student_session_student.class_student_id', '=', 'class_students.id')
            ->selectRaw('YEAR(class_student_sessions.session_date) as year, MONTH(class_student_sessions.session_date) as month, SUM(class_students.rate_per_meeting) as gross')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(fn ($sub) => $sub
                        ->whereMonth('class_student_sessions.session_date', $condition['month'])
                        ->whereYear('class_student_sessions.session_date', $condition['year'])
                    );
                }
            })
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', (int) $r->year, (int) $r->month));

        $grossSeries = [];
        $netSeries = [];
        foreach ($periods as $d) {
            $key = $d->format('Y-m');
            $g = (float) ($privatGross[$key]->gross ?? 0) + (float) ($classGross[$key]->gross ?? 0);
            $c = (float) ($cost[$key]->cost ?? 0);
            $grossSeries[] = $g;
            $netSeries[] = $g - $c;
        }

        return ['labels' => $labels, 'gross' => $grossSeries, 'net' => $netSeries];
    }

    private function buildStudentsChartByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): array
    {
        if ($mode === 'yearly') {
            $years = range($rangeStart->year, $rangeEnd->year);
            $labels = array_map(fn ($y) => (string) $y, $years);

            $rows = DB::table('monthly_student_snapshots')
                ->selectRaw('year, AVG(private_students_count) as private_avg, AVG(class_students_count) as class_avg')
                ->whereBetween('year', [$rangeStart->year, $rangeEnd->year])
                ->groupBy('year')
                ->get()
                ->keyBy(fn ($r) => (int) $r->year);

            $privateSeries = [];
            $classSeries = [];
            foreach ($years as $y) {
                $privateSeries[] = (int) round((float) ($rows[$y]->private_avg ?? 0));
                $classSeries[] = (int) round((float) ($rows[$y]->class_avg ?? 0));
            }

            return ['labels' => $labels, 'private' => $privateSeries, 'class' => $classSeries];
        }

        $periods = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $periods->push($cursor->copy());
            $cursor->addMonthNoOverflow();
        }

        $labels = $periods->map(fn ($d) => $d->format('M Y'))->values()->all();
        $pairs = $periods->map(fn ($d) => ['year' => $d->year, 'month' => $d->month])->values()->all();

        $rows = DB::table('monthly_student_snapshots')
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(fn ($sub) => $sub->where('year', $p['year'])->where('month', $p['month']));
                }
            })
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month));

        $privateSeries = [];
        $classSeries = [];
        foreach ($periods as $d) {
            $key = $d->format('Y-m');
            $privateSeries[] = (int) ($rows[$key]->private_students_count ?? 0);
            $classSeries[] = (int) ($rows[$key]->class_students_count ?? 0);
        }

        return ['labels' => $labels, 'private' => $privateSeries, 'class' => $classSeries];
    }

    private function buildTeachersChartByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): array
    {
        if ($mode === 'yearly') {
            $years = range($rangeStart->year, $rangeEnd->year);
            $labels = array_map(fn ($y) => (string) $y, $years);

            $rows = DB::table('monthly_teacher_snapshots')
                ->selectRaw('year, AVG(teachers_count) as teachers_avg')
                ->whereBetween('year', [$rangeStart->year, $rangeEnd->year])
                ->groupBy('year')
                ->get()
                ->keyBy(fn ($r) => (int) $r->year);

            $series = [];
            foreach ($years as $y) {
                $series[] = (int) round((float) ($rows[$y]->teachers_avg ?? 0));
            }

            return ['labels' => $labels, 'teachers' => $series];
        }

        $periods = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $periods->push($cursor->copy());
            $cursor->addMonthNoOverflow();
        }

        $labels = $periods->map(fn ($d) => $d->format('M Y'))->values()->all();
        $pairs = $periods->map(fn ($d) => ['year' => $d->year, 'month' => $d->month])->values()->all();

        $rows = DB::table('monthly_teacher_snapshots')
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(fn ($sub) => $sub->where('year', $p['year'])->where('month', $p['month']));
                }
            })
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month));

        $series = [];
        foreach ($periods as $d) {
            $key = $d->format('Y-m');
            $series[] = (int) ($rows[$key]->teachers_count ?? 0);
        }

        return ['labels' => $labels, 'teachers' => $series];
    }

    public function snapshotStudents(Request $request): \Illuminate\Http\RedirectResponse
    {
        $targetPeriod = now()->subMonth()->startOfMonth();
        $month = $targetPeriod->month;
        $year = $targetPeriod->year;
        $endOfMonth = $targetPeriod->copy()->endOfMonth();

        $privateStudentsCount = DB::table('enrollment_attendances')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->distinct('attendance_student.student_id')
            ->count('attendance_student.student_id');

        $classStudentsCount = DB::table('class_students')
            ->where('status', 'active')
            ->count();

        DB::table('monthly_student_snapshots')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            [
                'private_students_count' => $privateStudentsCount,
                'class_students_count' => $classStudentsCount,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('status', "Snapshot jumlah murid berhasil disimpan untuk {$month}/{$year}.");
    }

    public function snapshotTeachers(Request $request): \Illuminate\Http\RedirectResponse
    {
        $targetPeriod = now()->subMonth()->startOfMonth();
        $month = $targetPeriod->month;
        $year = $targetPeriod->year;
        $endOfMonth = $targetPeriod->copy()->endOfMonth();

        $teachersCount = DB::table('teachers')
            ->where('status', 'active')
            ->count();

        DB::table('monthly_teacher_snapshots')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            [
                'teachers_count' => $teachersCount,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('status', "Snapshot jumlah guru berhasil disimpan untuk {$month}/{$year}.");
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

        $classGrossQuery = DB::table('class_student_sessions')
            ->join('class_student_session_student', 'class_student_sessions.id', '=', 'class_student_session_student.class_student_session_id')
            ->join('class_students', 'class_student_session_student.class_student_id', '=', 'class_students.id')
            ->selectRaw('YEAR(class_student_sessions.session_date) as year, MONTH(class_student_sessions.session_date) as month, SUM(class_students.rate_per_meeting) as gross')
            ->where(function ($builder) use ($conditions) {
                foreach ($conditions as $condition) {
                    $builder->orWhere(function ($sub) use ($condition) {
                        $sub->whereMonth('class_student_sessions.session_date', $condition['month'])
                            ->whereYear('class_student_sessions.session_date', $condition['year']);
                    });
                }
            })
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(function ($row) {
                return sprintf('%04d-%02d', (int) $row->year, (int) $row->month);
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
            $grossValue = (float) ($byPeriod[$key]->gross ?? 0) + (float) ($classGrossQuery[$key]->gross ?? 0);
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
