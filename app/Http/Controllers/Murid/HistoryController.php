<?php

declare(strict_types=1);

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
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

        return view('murid.history.index', [
            'month' => $month,
            'year' => $year,
            'student' => $student,
            'attendances' => $attendances,
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
