<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $teacher = Teacher::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        $attendances = MonthlyAttendance::with(['lesson.student'])
            ->when($teacher, fn ($query) => $query->where('teacher_id', $teacher->id))
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(50)
            ->withQueryString();

        return view('guru.history.index', [
            'month' => $month,
            'year' => $year,
            'teacher' => $teacher,
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
