<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassReportController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->whereHas('enrollment.program', fn ($q) => $q->where('name', 'like', 'Kelas%'))
            ->whereBetween('lesson_date', [$monthStart, $monthEnd])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->get();

        $studentTotals = [];
        foreach ($attendances as $attendance) {
            foreach ($attendance->students as $student) {
                $id = $student->id;
                if (!isset($studentTotals[$id])) {
                    $studentTotals[$id] = [
                        'name' => $student->display_name,
                        'total' => 0,
                        'program' => $attendance->enrollment?->program?->name ?? '-',
                        'teacher' => $attendance->enrollment?->teacher?->name ?? '-',
                    ];
                }
                $studentTotals[$id]['total'] += (int) ($student->pivot?->total_present ?? 0);
            }
        }

        $rows = collect($studentTotals)->values();

        return view('admin.class-reports.index', [
            'month' => $month,
            'year' => $year,
            'rows' => $rows,
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
}