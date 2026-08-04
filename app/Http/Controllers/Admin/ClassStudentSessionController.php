<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassStudentSessionController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->whereHas('enrollment.program', fn ($q) => $q->where('name', 'like', 'Kelas%'))
            ->whereBetween('lesson_date', [$start, $end])
            ->orderBy('lesson_date')
            ->orderBy('created_at')
            ->get();

        $sessionsByDate = $attendances
            ->groupBy(fn ($attendance) => $attendance->lesson_date->format('Y-m-d'))
            ->map(function ($items) {
                return $items
                    ->groupBy(function ($attendance) {
                        return implode('|', [
                            $attendance->lesson_date->format('Y-m-d'),
                            $attendance->enrollment?->program?->name ?? '',
                            $attendance->enrollment?->teacher?->name ?? '',
                        ]);
                    })
                    ->map(function ($groupedItems) {
                        $first = $groupedItems->first();

                        return [
                            'attendance' => $first,
                            'enrollment' => $first->enrollment,
                            'students' => $groupedItems
                                ->flatMap(fn ($attendance) => $attendance->students)
                                ->unique('id')
                                ->values(),
                        ];
                    })
                    ->values();
            });

        return view('admin.class-student-sessions.calendar', [
            'month' => $month,
            'year' => $year,
            'start' => $start,
            'daysInMonth' => $start->daysInMonth,
            'firstDayOfWeek' => $start->dayOfWeekIso,
            'sessionsByDate' => $sessionsByDate,
        ]);
    }

    public function table(): View
    {
        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->whereHas('enrollment.program', fn ($q) => $q->where('name', 'like', 'Kelas%'))
            ->latest('lesson_date')
            ->get();

        return view('admin.class-student-sessions.table', compact('attendances'));
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