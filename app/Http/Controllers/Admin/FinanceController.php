<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\PaymentProof;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\CalculationService;
use App\Services\MonthlySnapshotSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        private CalculationService $calculationService,
    ) {}

    public function index(Request $request): View
    {
        $mode = $this->resolveMode($request);
        [$rangeStart, $rangeEnd] = $this->resolveRange($request, $mode);

        // All financial metrics use CalculationService, aggregated over the selected range
        $rangeFinance = $this->buildFinanceChartByRange($rangeStart, $rangeEnd, $mode);
        $privatGrossRange = array_sum($rangeFinance['privatGross']);
        $kelasGrossRange = array_sum($rangeFinance['kelasGross']);
        $privatNetRange = array_sum($rangeFinance['privatNet']);
        $kelasNetRange = array_sum($rangeFinance['kelasNet']);

        $allTeacherSalariesRange = $this->buildTeacherCostByRange($rangeStart, $rangeEnd, $mode);
        $privatTeacherCost = $allTeacherSalariesRange['privat'];
        $kelasTeacherCost = $allTeacherSalariesRange['kelas'];
        $teacherCost = $privatTeacherCost + $kelasTeacherCost;

        $gross = $privatGrossRange + $kelasGrossRange;
        $net = $gross - $teacherCost;

        $chartFinance = $rangeFinance;
        $chartStudents = $this->buildStudentsChartByRange($rangeStart, $rangeEnd, $mode);
        $chartTeachers = $this->buildTeachersChartByRange($rangeStart, $rangeEnd, $mode);

        // Build a readable label for the selected period
        $singleMonth = $rangeStart->format('Y-m') === $rangeEnd->format('Y-m');
        $periodLabel = $singleMonth
            ? $rangeStart->format('F Y')
            : $rangeStart->format('M Y') . ' – ' . $rangeEnd->format('M Y');

        // Global counts (not period-specific)
        $activeStudents = Student::query()->where('status', 'active')->count();
        $activeTeachers = Teacher::query()->where('status', 'active')->count();

        // needsFix: count rejected attendances across the full range using CalculationService context
        $needsFix = $this->countNeedsFixByRange($rangeStart, $rangeEnd, $mode);

        // Snapshot-based counts: average across the range for a stable KPI
        [$activeClassStudents, $activePrivateStudents] = $this->avgStudentSnapshotsByRange($rangeStart, $rangeEnd, $mode);
        $activeTeachersPeriod = $this->avgTeacherSnapshotsByRange($rangeStart, $rangeEnd, $mode);

        // Single-month values (for URL param links that need a specific month)
        [$month, $year] = $this->resolvePeriod($request);

        return view('admin.finance.dashboard', [
            'month' => $month,
            'year' => $year,
            'periodLabel' => $periodLabel,
            'privatGross' => (int) $privatGrossRange,
            'kelasGross' => (int) $kelasGrossRange,
            'privatTeacherCost' => (int) $privatTeacherCost,
            'kelasTeacherCost' => (int) $kelasTeacherCost,
            'privatNet' => (int) $privatNetRange,
            'kelasNet' => (int) $kelasNetRange,
            'activeStudents' => $activeStudents,
            'activeClassStudents' => $activeClassStudents,
            'activePrivateStudents' => $activePrivateStudents,
            'activeTeachers' => $activeTeachers,
            'activeTeachersPeriod' => $activeTeachersPeriod,
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
        // Pre-fetch all attendances for the range with needed relations
        $allAttendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'sessionTeacher',
            'students',
        ])
            ->where('year', '>=', $rangeStart->year)
            ->where('year', '<=', $rangeEnd->year)
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where(fn ($q) =>
                $q->whereNull('parent_review_status')
                  ->orWhere('parent_review_status', '!=', 'pending')
            )
            ->get();

        if ($mode === 'yearly') {
            $years = range($rangeStart->year, $rangeEnd->year);
            $labels = array_map(fn ($y) => (string) $y, $years);

            $privatGrossSeries = [];
            $privatNetSeries = [];
            $kelasGrossSeries = [];
            $kelasNetSeries = [];

            foreach ($years as $y) {
                $yearAttendances = $allAttendances->where('year', $y);
                $privatAtt = $yearAttendances->filter(fn ($a) => $a->enrollment?->isPrivat());
                $classAtt = $yearAttendances->filter(fn ($a) => $a->enrollment?->isKelas());

                $privatBillings = $this->calculationService->calculateAllStudentBillings(1, $y, $privatAtt);
                $classBillings = $this->calculationService->calculateAllStudentBillings(1, $y, $classAtt);
                $allSalaries = $this->calculationService->calculateAllTeacherSalaries(1, $y, $yearAttendances);

                $pg = collect($privatBillings)->sum(fn ($b) => $b['billing']['grand_total'] ?? 0);
                $kg = collect($classBillings)->sum(fn ($b) => $b['billing']['grand_total'] ?? 0);

                $privatCost = 0;
                $kelasCost = 0;
                foreach ($allSalaries as $salary) {
                    $rows = $salary['billing']['rows'] ?? collect();
                    $privatCost += $rows->whereIn('type', ['privat', 'privat_tanpa_murid'])->sum('total');
                    $kelasCost += $rows->whereIn('type', ['kelas', 'kelas_tanpa_murid'])->sum('total');
                }

                $privatGrossSeries[] = $pg;
                $privatNetSeries[] = $pg - $privatCost;
                $kelasGrossSeries[] = $kg;
                $kelasNetSeries[] = $kg - $kelasCost;
            }

            return [
                'labels' => $labels,
                'privatGross' => $privatGrossSeries,
                'privatNet' => $privatNetSeries,
                'kelasGross' => $kelasGrossSeries,
                'kelasNet' => $kelasNetSeries,
            ];
        }

        $periods = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $periods->push($cursor->copy());
            $cursor->addMonthNoOverflow();
        }

        $labels = $periods->map(fn ($d) => $d->format('M Y'))->values()->all();

        $privatGrossSeries = [];
        $privatNetSeries = [];
        $kelasGrossSeries = [];
        $kelasNetSeries = [];

        foreach ($periods as $d) {
            $m = $d->month;
            $y = $d->year;
            $periodAtt = $allAttendances->filter(fn ($a) => $a->month === $m && $a->year === $y);
            $privatAtt = $periodAtt->filter(fn ($a) => $a->enrollment?->isPrivat());
            $classAtt = $periodAtt->filter(fn ($a) => $a->enrollment?->isKelas());

            $privatBillings = $this->calculationService->calculateAllStudentBillings($m, $y, $privatAtt);
            $classBillings = $this->calculationService->calculateAllStudentBillings($m, $y, $classAtt);
            $allSalaries = $this->calculationService->calculateAllTeacherSalaries($m, $y, $periodAtt);

            $pg = collect($privatBillings)->sum(fn ($b) => $b['billing']['grand_total'] ?? 0);
            $kg = collect($classBillings)->sum(fn ($b) => $b['billing']['grand_total'] ?? 0);

            $privatCost = 0;
            $kelasCost = 0;
            foreach ($allSalaries as $salary) {
                $rows = $salary['billing']['rows'] ?? collect();
                $privatCost += $rows->whereIn('type', ['privat', 'privat_tanpa_murid'])->sum('total');
                $kelasCost += $rows->whereIn('type', ['kelas', 'kelas_tanpa_murid'])->sum('total');
            }

            $privatGrossSeries[] = $pg;
            $privatNetSeries[] = $pg - $privatCost;
            $kelasGrossSeries[] = $kg;
            $kelasNetSeries[] = $kg - $kelasCost;
        }

        return [
            'labels' => $labels,
            'privatGross' => $privatGrossSeries,
            'privatNet' => $privatNetSeries,
            'kelasGross' => $kelasGrossSeries,
            'kelasNet' => $kelasNetSeries,
        ];
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

    private function buildTeacherCostByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): array
    {
        $allAttendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'sessionTeacher',
            'students',
        ])
            ->where('year', '>=', $rangeStart->year)
            ->where('year', '<=', $rangeEnd->year)
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where(fn ($q) =>
                $q->whereNull('parent_review_status')
                  ->orWhere('parent_review_status', '!=', 'pending')
            )
            ->get();

        $privatTotal = 0;
        $kelasTotal = 0;

        if ($mode === 'yearly') {
            foreach (range($rangeStart->year, $rangeEnd->year) as $y) {
                $yearAtt = $allAttendances->where('year', $y);
                $salaries = $this->calculationService->calculateAllTeacherSalaries(1, $y, $yearAtt);
                [$p, $k] = $this->sumTeacherCostFromSalaries($salaries);
                $privatTotal += $p;
                $kelasTotal += $k;
            }
        } else {
            $cursor = $rangeStart->copy()->startOfMonth();
            while ($cursor->lte($rangeEnd)) {
                $m = $cursor->month;
                $y = $cursor->year;
                $periodAtt = $allAttendances->filter(fn ($a) => $a->month === $m && $a->year === $y);
                $salaries = $this->calculationService->calculateAllTeacherSalaries($m, $y, $periodAtt);
                [$p, $k] = $this->sumTeacherCostFromSalaries($salaries);
                $privatTotal += $p;
                $kelasTotal += $k;
                $cursor->addMonthNoOverflow();
            }
        }

        return ['privat' => (int) $privatTotal, 'kelas' => (int) $kelasTotal];
    }

    private function countNeedsFixByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): int
    {
        if ($mode === 'yearly') {
            return MonthlyAttendance::query()
                ->where('status_validation', 'ditolak')
                ->whereBetween('year', [$rangeStart->year, $rangeEnd->year])
                ->count();
        }

        $pairs = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $pairs->push(['year' => $cursor->year, 'month' => $cursor->month]);
            $cursor->addMonthNoOverflow();
        }

        return MonthlyAttendance::query()
            ->where('status_validation', 'ditolak')
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(fn ($sub) => $sub->where('year', $p['year'])->where('month', $p['month']));
                }
            })
            ->count();
    }

    private function avgStudentSnapshotsByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): array
    {
        if ($mode === 'yearly') {
            $row = DB::table('monthly_student_snapshots')
                ->whereBetween('year', [$rangeStart->year, $rangeEnd->year])
                ->selectRaw('AVG(class_students_count) as class_avg, AVG(private_students_count) as private_avg')
                ->first();
            return [(int) round((float) ($row->class_avg ?? 0)), (int) round((float) ($row->private_avg ?? 0))];
        }

        $pairs = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $pairs->push(['year' => $cursor->year, 'month' => $cursor->month]);
            $cursor->addMonthNoOverflow();
        }

        $row = DB::table('monthly_student_snapshots')
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(fn ($sub) => $sub->where('year', $p['year'])->where('month', $p['month']));
                }
            })
            ->selectRaw('AVG(class_students_count) as class_avg, AVG(private_students_count) as private_avg')
            ->first();
        return [(int) round((float) ($row->class_avg ?? 0)), (int) round((float) ($row->private_avg ?? 0))];
    }

    private function avgTeacherSnapshotsByRange(Carbon $rangeStart, Carbon $rangeEnd, string $mode): int
    {
        if ($mode === 'yearly') {
            $avg = DB::table('monthly_teacher_snapshots')
                ->whereBetween('year', [$rangeStart->year, $rangeEnd->year])
                ->selectRaw('AVG(teachers_count) as avg')
                ->value('avg');
            return (int) round((float) ($avg ?? 0));
        }

        $pairs = collect();
        $cursor = $rangeStart->copy()->startOfMonth();
        while ($cursor->lte($rangeEnd)) {
            $pairs->push(['year' => $cursor->year, 'month' => $cursor->month]);
            $cursor->addMonthNoOverflow();
        }

        $avg = DB::table('monthly_teacher_snapshots')
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(fn ($sub) => $sub->where('year', $p['year'])->where('month', $p['month']));
                }
            })
            ->selectRaw('AVG(teachers_count) as avg')
            ->value('avg');
        return (int) round((float) ($avg ?? 0));
    }

    private function sumTeacherCostFromSalaries(array $salaries): array
    {
        $privat = 0;
        $kelas = 0;
        foreach ($salaries as $salary) {
            $rows = $salary['billing']['rows'] ?? collect();
            $privat += $rows->whereIn('type', ['privat', 'privat_tanpa_murid'])->sum('total');
            $kelas += $rows->whereIn('type', ['kelas', 'kelas_tanpa_murid'])->sum('total');
        }
        return [$privat, $kelas];
    }

    // ─── Ringkasan Payment Views ───────────────────────────────────────────────

    private function monthName(int $month): string
    {
        $names = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $names[$month] ?? 'Bulan';
    }

    public function ortuSummary(): View
    {
        $allAttendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'sessionTeacher',
            'students',
        ])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where(fn ($q) =>
                $q->whereNull('parent_review_status')
                  ->orWhere('parent_review_status', '!=', 'pending')
            )
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        if ($allAttendances->isEmpty()) {
            return view('admin.payments.ortu-summary', [
                'months' => [],
                'parents' => collect(),
                'cellMap' => [],
                'proofMap' => [],
            ]);
        }

        // Step 1: Distinct months sorted ASC
        $months = $allAttendances
            ->map(fn ($a) => ['month' => $a->month, 'year' => $a->year])
            ->unique(fn ($p) => $p['month'] . '-' . $p['year'])
            ->sort(fn ($a, $b) =>
                $a['year'] !== $b['year']
                    ? $a['year'] <=> $b['year']
                    : $a['month'] <=> $b['month']
            )
            ->values()
            ->all();

        // Step 2: Build per-(parent, month) cell data
        $cellMap = []; // key: "parentId-month-year"
        foreach ($allAttendances as $attendance) {
            foreach ($attendance->students as $student) {
                if (($student->pivot->total_present ?? 0) <= 0) {
                    continue;
                }
                $parent = $student->parent;
                if (!$parent) {
                    continue;
                }
                $key = $parent->id . '-' . $attendance->month . '-' . $attendance->year;
                if (!isset($cellMap[$key])) {
                    $cellMap[$key] = [
                        'parent_id' => $parent->id,
                        'month' => $attendance->month,
                        'year' => $attendance->year,
                        'grand_total' => 0,
                        'payment_status' => null,
                        'attendance_ids' => [],
                    ];
                }
                $cellMap[$key]['attendance_ids'][] = $attendance->id;
            }
        }

        // Step 3: Compute grand_total per cell and aggregate payment status
        foreach ($cellMap as $key => &$cell) {
            $cellParentId = $cell['parent_id'];

            // Sum grand_total across all students of this parent for this month
            $totalGrand = 0;
            $studentIdsSeen = [];
            foreach ($allAttendances as $a) {
                if ($a->month !== $cell['month'] || $a->year !== $cell['year']) {
                    continue;
                }
                foreach ($a->students as $s) {
                    if ($s->parent_id !== $cellParentId) {
                        continue;
                    }
                    if (($s->pivot->total_present ?? 0) <= 0) {
                        continue;
                    }
                    if (isset($studentIdsSeen[$s->id])) {
                        continue;
                    }
                    $studentIdsSeen[$s->id] = true;

                    $studentAtt = $allAttendances->filter(
                        fn ($att) =>
                            $att->month === $cell['month']
                            && $att->year === $cell['year']
                            && $att->students->contains('id', $s->id)
                            && ($att->students->firstWhere('id', $s->id)->pivot->total_present ?? 0) > 0
                    );

                    $result = $this->calculationService->calculateStudentBilling(
                        $s, $cell['month'], $cell['year'], $studentAtt
                    );
                    $totalGrand += $result['grand_total'];
                }
            }
            $cell['grand_total'] = $totalGrand;

            // Aggregate payment_status
            if (!empty($cell['attendance_ids'])) {
                $paidCount = MonthlyAttendance::whereIn('id', $cell['attendance_ids'])
                    ->where('parent_payment_status', 'paid')->count();
                $totalCount = MonthlyAttendance::whereIn('id', $cell['attendance_ids'])->count();
                $cell['payment_status'] = ($paidCount > 0 && $paidCount === $totalCount) ? 'paid' : 'unpaid';
            }
        }
        unset($cell);

        // Step 4: Approved PaymentProof map
        $proofMap = [];
        foreach ($months as $p) {
            $proofs = PaymentProof::where('month', $p['month'])
                ->where('year', $p['year'])
                ->where('status', 'approved')
                ->pluck('parent_id')
                ->unique();
            foreach ($proofs as $pid) {
                $proofMap[$pid . '-' . $p['month'] . '-' . $p['year']] = true;
            }
        }

        // Step 5: Parents who have data (ParentModel has no status column; filter by active students)
        $parentIdsWithData = collect($cellMap)->pluck('parent_id')->unique()->values()->all();
        $parents = ParentModel::whereIn('id', $parentIdsWithData)
            ->with(['students' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->sortBy('name')
            ->values();

        return view('admin.payments.ortu-summary', [
            'months' => $months,
            'parents' => $parents,
            'cellMap' => $cellMap,
            'proofMap' => $proofMap,
            'monthName' => fn (int $m) => $this->monthName($m),
        ]);
    }

    public function guruSummary(): View
    {
        $allAttendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'sessionTeacher',
            'students',
        ])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        if ($allAttendances->isEmpty()) {
            return view('admin.payments.guru-summary', [
                'months' => [],
                'teachers' => collect(),
                'cellMap' => [],
            ]);
        }

        // Step 1: Distinct months
        $months = $allAttendances
            ->map(fn ($a) => ['month' => $a->month, 'year' => $a->year])
            ->unique(fn ($p) => $p['month'] . '-' . $p['year'])
            ->sort(fn ($a, $b) =>
                $a['year'] !== $b['year']
                    ? $a['year'] <=> $b['year']
                    : $a['month'] <=> $b['month']
            )
            ->values()
            ->all();

        // Step 2: Identify active teachers with attendance records
        $allTeacherIds = collect()
            ->merge($allAttendances->pluck('enrollment.teacher')->filter()->unique('id')->pluck('id'))
            ->merge($allAttendances->pluck('sessionTeacher')->filter()->unique('id')->pluck('id'))
            ->unique(fn ($id) => $id)
            ->values();

        $teachers = Teacher::whereIn('id', $allTeacherIds)
            ->where('status', 'active')
            ->get()
            ->sortBy('full_name')
            ->values();

        // Step 3: Build cell map per teacher+month
        $cellMap = [];
        foreach ($teachers as $teacher) {
            $teacherId = $teacher->id;
            foreach ($months as $p) {
                $month = $p['month'];
                $year = $p['year'];

                $teacherAttendances = $allAttendances->filter(
                    fn (MonthlyAttendance $a) =>
                        $a->month === $month && $a->year === $year
                        && (
                            ($a->enrollment && !$a->enrollment->isKelas()
                                && (int) ($a->enrollment->teacher_id ?? 0) === $teacherId)
                            || ((int) ($a->session_teacher_id ?? 0) === $teacherId)
                        )
                );

                if ($teacherAttendances->isEmpty()) {
                    continue;
                }

                $result = $this->calculationService->calculateTeacherSalary(
                    $teacherId, $month, $year, $teacherAttendances
                );

                $attendanceIds = collect($result['rows'])
                    ->flatMap(fn (array $row) => $row['attendance_ids'] ?? [])
                    ->unique()
                    ->values()
                    ->toArray();

                $allPaid = !empty($attendanceIds)
                    && MonthlyAttendance::whereIn('id', $attendanceIds)
                        ->where('teacher_payment_status', 'paid')->count() === count($attendanceIds);
                $anyHeld = !empty($attendanceIds)
                    && MonthlyAttendance::whereIn('id', $attendanceIds)
                        ->where('teacher_payment_status', 'held')->exists();

                $paymentStatus = $allPaid ? 'paid' : ($anyHeld ? 'held' : 'unpaid');

                $cellMap[$teacherId . '-' . $month . '-' . $year] = [
                    'teacher_id' => $teacherId,
                    'month' => $month,
                    'year' => $year,
                    'final_total' => $result['final_total'],
                    'payment_status' => $paymentStatus,
                    'attendance_ids' => $attendanceIds,
                ];
            }
        }

        return view('admin.payments.guru-summary', [
            'months' => $months,
            'teachers' => $teachers,
            'cellMap' => $cellMap,
            'monthName' => fn (int $m) => $this->monthName($m),
        ]);
    }

    public function updateOrtuPaymentStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['required', 'integer', 'exists:parents,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'status' => ['required', 'in:unpaid,paid'],
        ]);

        $studentIds = Student::where('parent_id', $validated['parent_id'])->pluck('id');

        $updated = MonthlyAttendance::where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->update(['parent_payment_status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'status' => $validated['status'],
        ]);
    }

    public function updateGuruPaymentStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'status' => ['required', 'in:unpaid,paid,held'],
        ]);

        $updated = MonthlyAttendance::where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->where(fn ($q) =>
                $q->whereHas('enrollment', fn ($eq) =>
                    $eq->where('teacher_id', $validated['teacher_id'])
                )
                ->orWhere('session_teacher_id', $validated['teacher_id'])
            )
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->update(['teacher_payment_status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'status' => $validated['status'],
        ]);
    }
}