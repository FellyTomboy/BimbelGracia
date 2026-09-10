<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\CalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function students(Request $request): View
    {
        $students = Student::orderByRaw('COALESCE(full_name, nickname)')->get();
        $studentId = $request->input('student_id');
        $month = $request->input('month');
        $year = $request->input('year');

        // Paginate per period (year-month), not per record
        [$grouped, $periodPagination] = $this->buildPeriodPagination(
            MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'sessionTeacher', 'students'])
                ->when($studentId, fn ($query) => $query->whereHas('students', fn ($sub) => $sub->where('students.id', $studentId)))
                ->when($month, fn ($query) => $query->where('month', (int) $month))
                ->when($year, fn ($query) => $query->where('year', (int) $year)),
            $request
        );

        // When no student filter: pre-compute all billings via CalculationService
        $studentBillings = null;
        if (! $studentId) {
            $calcService = app(CalculationService::class);
            $periodAttendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'sessionTeacher', 'students'])
                ->when($month, fn ($query) => $query->where('month', (int) $month))
                ->when($year, fn ($query) => $query->where('year', (int) $year))
                ->get();
            $studentBillings = $calcService->calculateAllStudentBillings(
                (int) ($month ?: now()->month),
                (int) ($year ?: now()->year),
                $periodAttendances
            );
        }

        return view('admin.history.students', compact(
            'students', 'studentId', 'month', 'year', 'grouped', 'periodPagination', 'studentBillings'
        ));
    }

    public function teachers(Request $request): View
    {
        $teachers = Teacher::orderBy('full_name')->get();
        $teacherId = $request->input('teacher_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $baseQuery = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'sessionTeacher', 'students'])
            ->when($teacherId, function ($query) use ($teacherId) {
                $query->where(function ($q) use ($teacherId) {
                    $q->whereHas('enrollment', fn ($sub) => $sub->where('teacher_id', $teacherId))
                      ->orWhere('session_teacher_id', $teacherId);
                });
            })
            ->when($month, fn ($query) => $query->where('month', (int) $month))
            ->when($year, fn ($query) => $query->where('year', (int) $year));

        [$grouped, $periodPagination] = $this->buildPeriodPagination($baseQuery, $request);

        // When no teacher filter: pre-compute all salaries via CalculationService
        $teacherSalaries = null;
        if (! $teacherId) {
            $calcService = app(CalculationService::class);
            $periodAttendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'sessionTeacher', 'students'])
                ->when($month, fn ($query) => $query->where('month', (int) $month))
                ->when($year, fn ($query) => $query->where('year', (int) $year))
                ->get();
            $teacherSalaries = $calcService->calculateAllTeacherSalaries(
                (int) ($month ?: now()->month),
                (int) ($year ?: now()->year),
                $periodAttendances
            );
        }

        return view('admin.history.teachers', compact(
            'teachers', 'teacherId', 'month', 'year', 'grouped', 'periodPagination', 'teacherSalaries'
        ));
    }

    public function payments(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('enrollment_id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.history.payments', compact('month', 'year', 'attendances'));
    }

    public function audit(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.history.audit', compact('logs'));
    }

    private function buildPeriodPagination($baseQuery, Request $request): array
    {
        $perPage = 12;

        // Get distinct periods ordered desc
        $periods = (clone $baseQuery)
            ->selectRaw('DISTINCT year, month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn ($row) => ['year' => $row->year, 'month' => $row->month])
            ->values();

        // Paginate periods manually
        $page = max(1, (int) $request->input('page', 1));
        $total = $periods->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $currentPeriods = $periods->forPage($page, $perPage)->values();

        // Build period pagination links manually
        $periodPagination = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPeriods,
            $total,
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        // Fetch all records for the current page's periods
        $periodList = $currentPeriods->map(fn ($p) => ['y' => $p['year'], 'm' => $p['month']])->all();
        if (empty($periodList)) {
            return [collect(), $periodPagination];
        }

        $allForPeriods = (clone $baseQuery)
            ->where(function ($q) use ($periodList) {
                foreach ($periodList as $p) {
                    $q->orWhere(fn ($sub) => $sub
                        ->where('year', $p['y'])
                        ->where('month', $p['m']));
                }
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('enrollment_id')
            ->get();

        $grouped = $allForPeriods->groupBy(fn ($a) => $a->year.'-'.$a->month);

        return [$grouped, $periodPagination];
    }
}
