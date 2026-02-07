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

        $attendances = MonthlyAttendance::with(['lesson.teacher', 'student'])
            ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(50)
            ->withQueryString();

        return view('admin.history.students', compact('students', 'studentId', 'attendances'));
    }

    public function teachers(Request $request): View
    {
        $teachers = Teacher::orderBy('name')->get();
        $teacherId = $request->input('teacher_id');

        $attendances = MonthlyAttendance::with(['lesson.student', 'teacher'])
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(50)
            ->withQueryString();

        return view('admin.history.teachers', compact('teachers', 'teacherId', 'attendances'));
    }

    public function payments(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        $attendances = MonthlyAttendance::with(['lesson.teacher', 'lesson.student', 'student', 'teacher'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('student_id')
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
