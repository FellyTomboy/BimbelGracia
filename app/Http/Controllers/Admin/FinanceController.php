<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\MonthlySnapshotSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        // Private gross revenue
        $privatGross = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->where('programs.name', 'not like', 'Kelas%')
            ->sum(DB::raw('attendance_student.total_present * enrollment_attendances.parent_rate'));

        // Class gross revenue (programs named "Kelas%")
        $classGross = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->where('programs.name', 'like', 'Kelas%')
            ->sum(DB::raw('attendance_student.total_present * enrollment_attendances.parent_rate'));

        // Teacher cost: full rate for 'terima', 90% rate for 'terlambat' (10% penalty)
        $privatTeacherCost = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->selectRaw('
                SUM(CASE WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate ELSE 0 END) +
                SUM(CASE WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate * 0.9 ELSE 0 END) as total
            ', ['terima', 'terlambat'])
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->where('programs.name', 'not like', 'Kelas%')
            ->value('total') ?? 0;

        $gross = $privatGross + $classGross;
        $teacherCost = (int) $privatTeacherCost;

        $net = $gross - $teacherCost;

        $activeStudents = Student::query()
            ->where('status', 'active')
            ->count();

        $activeTeachers = Teacher::query()
            ->where('status', 'active')
            ->count();

        $needsFix = MonthlyAttendance::query()
            ->where('status_validation', 'ditolak')
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
            'activeClassStudents' => 0,
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

    public function snapshotStudents(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        app(MonthlySnapshotSyncService::class)->syncStudentSnapshotsForPeriod(
            (int) $validated['month'],
            (int) $validated['year']
        );

        return back()->with('status', 'Snapshot murid berhasil disimpan.');
    }

    public function snapshotTeachers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        app(MonthlySnapshotSyncService::class)->syncTeacherSnapshotsForPeriod(
            (int) $validated['month'],
            (int) $validated['year']
        );

        return back()->with('status', 'Snapshot guru berhasil disimpan.');
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
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

        $endDefaultCarbon = $now->copy()->startOfMonth();
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
                ->join('programs', 'enrollments.program_id', '=', 'programs.id')
                ->selectRaw('enrollment_attendances.year, SUM(attendance_student.total_present * enrollment_attendances.parent_rate) as gross')
                ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
                ->whereBetween('enrollment_attendances.year', [$rangeStart->year, $rangeEnd->year])
                ->where('programs.name', 'not like', 'Kelas%')
                ->groupBy('enrollment_attendances.year')
                ->pluck('gross', 'year');

            $classGross = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
                ->join('programs', 'enrollments.program_id', '=', 'programs.id')
                ->selectRaw('enrollment_attendances.year, SUM(attendance_student.total_present * enrollment_attendances.parent_rate) as gross')
                ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
                ->whereBetween('enrollment_attendances.year', [$rangeStart->year, $rangeEnd->year])
                ->where('programs.name', 'like', 'Kelas%')
                ->groupBy('enrollment_attendances.year')
                ->pluck('gross', 'year');

            $cost = DB::table('enrollment_attendances')
                ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
                ->join('programs', 'enrollments.program_id', '=', 'programs.id')
                ->selectRaw('enrollment_attendances.year, SUM(CASE WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate * 0.9 ELSE 0 END) as cost', ['terima', 'terlambat'])
                ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
                ->whereBetween('enrollment_attendances.year', [$rangeStart->year, $rangeEnd->year])
                ->where('programs.name', 'not like', 'Kelas%')
                ->groupBy('enrollment_attendances.year')
                ->pluck('cost', 'year');

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
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(attendance_student.total_present * enrollment_attendances.parent_rate) as gross')
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where('programs.name', 'not like', 'Kelas%')
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

        $classGross = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('attendance_student', 'enrollment_attendances.id', '=', 'attendance_student.attendance_id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(attendance_student.total_present * enrollment_attendances.parent_rate) as gross')
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where('programs.name', 'like', 'Kelas%')
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
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->selectRaw('enrollment_attendances.year, enrollment_attendances.month, SUM(CASE WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate WHEN enrollment_attendances.status_validation = ? THEN enrollment_attendances.teacher_rate * 0.9 ELSE 0 END) as cost', ['terima', 'terlambat'])
            ->whereIn('enrollment_attendances.status_validation', ['terima', 'terlambat'])
            ->where('programs.name', 'not like', 'Kelas%')
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
                ->selectRaw('year, AVG(teachers_count) as avg')
                ->whereBetween('year', [$rangeStart->year, $rangeEnd->year])
                ->groupBy('year')
                ->get()
                ->keyBy(fn ($r) => (int) $r->year);

            $series = [];
            foreach ($years as $y) {
                $series[] = (int) round((float) ($rows[$y]->avg ?? 0));
            }

            return ['labels' => $labels, 'series' => $series];
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

        return ['labels' => $labels, 'series' => $series];
    }
}