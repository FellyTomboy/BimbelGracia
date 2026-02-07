<?php

declare(strict_types=1);

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $student = Student::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when($student, fn ($query) => $query->whereHas('students', fn ($sub) => $sub->where('students.id', $student->id)))
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $totals = $this->buildTotals($attendances, $student?->id);

        return view('murid.billing.index', [
            'month' => $month,
            'year' => $year,
            'student' => $student,
            'attendances' => $attendances,
            'totals' => $totals,
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

    private function buildTotals($attendances, ?int $studentId): array
    {
        $rows = $attendances->map(function (MonthlyAttendance $attendance) use ($studentId) {
            $student = $attendance->students->firstWhere('id', $studentId);
            $present = (int) ($student?->pivot?->total_present ?? 0);
            $rate = $attendance->enrollment?->parent_rate ?? 0;
            $total = $present * $rate;

            return [
                'status' => $attendance->parent_payment_status ?? 'unknown',
                'total' => $total,
            ];
        });

        return [
            'paid' => (int) $rows->where('status', 'paid')->sum('total'),
            'unpaid' => (int) $rows->where('status', 'unpaid')->sum('total'),
            'partial' => (int) $rows->where('status', 'partial')->sum('total'),
            'unknown' => (int) $rows->where('status', 'unknown')->sum('total'),
            'grand' => (int) $rows->sum('total'),
        ];
    }
}
