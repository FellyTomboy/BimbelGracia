<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassReportController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $studentRows = DB::table('class_session_student')
            ->join('class_sessions', 'class_session_student.class_session_id', '=', 'class_sessions.id')
            ->where('class_sessions.session_date', '>=', Carbon::create($year, $month, 1)->startOfMonth())
            ->where('class_sessions.session_date', '<=', Carbon::create($year, $month, 1)->endOfMonth())
            ->where('class_session_student.is_present', true)
            ->selectRaw('class_session_student.student_id, COUNT(*) as total')
            ->groupBy('class_session_student.student_id')
            ->get();

        $studentTotals = $studentRows->pluck('total', 'student_id');
        $students = Student::query()
            ->whereIn('id', $studentTotals->keys())
            ->orderBy('name')
            ->get();

        $teacherRows = DB::table('class_sessions')
            ->where('session_date', '>=', Carbon::create($year, $month, 1)->startOfMonth())
            ->where('session_date', '<=', Carbon::create($year, $month, 1)->endOfMonth())
            ->selectRaw('teacher_id, COUNT(*) as total')
            ->groupBy('teacher_id')
            ->get();

        $teacherTotals = $teacherRows->pluck('total', 'teacher_id');
        $teachers = Teacher::query()
            ->whereIn('id', $teacherTotals->keys())
            ->orderBy('name')
            ->get();

        return view('admin.class-reports.index', [
            'month' => $month,
            'year' => $year,
            'students' => $students,
            'studentTotals' => $studentTotals,
            'teachers' => $teachers,
            'teacherTotals' => $teacherTotals,
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
