<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
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

        $studentRows = DB::table('class_student_sessions')
            ->where('session_date', '>=', Carbon::create($year, $month, 1)->startOfMonth())
            ->where('session_date', '<=', Carbon::create($year, $month, 1)->endOfMonth())
            ->selectRaw('class_student_id, COUNT(*) as total')
            ->groupBy('class_student_id')
            ->get();

        $studentTotals = $studentRows->pluck('total', 'class_student_id');
        $students = ClassStudent::query()
            ->whereIn('id', $studentTotals->keys())
            ->orderBy('name')
            ->get();

        $teacherRows = DB::table('enrollment_attendances')
            ->join('enrollments', 'enrollment_attendances.enrollment_id', '=', 'enrollments.id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->where('enrollment_attendances.status_validation', 'valid')
            ->where('enrollment_attendances.month', $month)
            ->where('enrollment_attendances.year', $year)
            ->where('programs.type', 'kelas')
            ->selectRaw('enrollments.teacher_id, SUM(enrollment_attendances.total_lessons) as total')
            ->groupBy('enrollments.teacher_id')
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
