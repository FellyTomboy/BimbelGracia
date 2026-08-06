<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function students(Request $request): View
    {
        $students = Student::orderBy('name')->get();
        $studentId = $request->input('student_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when($studentId, fn ($query) => $query->whereHas('students', fn ($sub) => $sub->where('students.id', $studentId)))
            ->when($month, fn ($query) => $query->where('month', (int) $month))
            ->when($year, fn ($query) => $query->where('year', (int) $year))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('enrollment_id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.history.students', compact('students', 'studentId', 'month', 'year', 'attendances'));
    }

    public function teachers(Request $request): View
    {
        $teachers = Teacher::orderBy('name')->get();
        $teacherId = $request->input('teacher_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when($teacherId, fn ($query) => $query->whereHas('enrollment', fn ($sub) => $sub->where('teacher_id', $teacherId)))
            ->when($month, fn ($query) => $query->where('month', (int) $month))
            ->when($year, fn ($query) => $query->where('year', (int) $year))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('enrollment_id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.history.teachers', compact('teachers', 'teacherId', 'month', 'year', 'attendances'));
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
}
