<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassStudentSessionController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $start = Carbon::create($year, $month, 1)->startOfMonth();

        $sessions = ClassSession::with(['program', 'teachers', 'attendances.enrollment', 'attendances.students'])
            ->whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->orderBy('session_date')
            ->get()
            ->groupBy(fn ($s) => $s->program_id);

        return view('admin.class-student-sessions.calendar', [
            'month' => $month,
            'year' => $year,
            'start' => $start,
            'daysInMonth' => $start->daysInMonth,
            'firstDayOfWeek' => $start->dayOfWeekIso,
            'sessions' => $sessions,
            'programs' => Program::where('type', 'kelas')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function table(): View
    {
        $sessions = ClassSession::with(['program', 'teachers', 'attendances.enrollment', 'attendances.students'])
            ->latest('session_date')
            ->get();

        return view('admin.class-student-sessions.table', [
            'sessions' => $sessions,
        ]);
    }

    public function create(Request $request): View
    {
        $month = (int) ($request->query('month') ?? now()->month);
        $year = (int) ($request->query('year') ?? now()->year);
        $programId = (int) ($request->query('program_id') ?? 0);

        $programs = Program::where('type', 'kelas')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $selectedProgram = $programs->firstWhere('id', $programId);

        // Fetch ALL kelas enrollments (from all programs) so JS can filter by program dynamically
        $enrollments = Enrollment::with(['students'])
            ->whereIn('program_id', $programs->pluck('id'))
            ->where('type', 'kelas')
            ->where('status', 'active')
            ->withTrashed()
            ->get();

        $allStudents = [];
        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->students as $student) {
                $allStudents[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->display_name,
                    'enrollment_id' => $enrollment->id,
                    'program_id' => $enrollment->program_id,
                ];
            }
        }

        $teachers = Teacher::orderBy('name')->get(['id', 'name']);
        $teachersList = $teachers
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->toArray();

        return view('admin.class-student-sessions.create', [
            'programs' => $programs,
            'selectedProgram' => $selectedProgram,
            'enrollments' => $enrollments,
            'allStudents' => $allStudents,
            'teachers' => $teachers,
            'teachersList' => $teachersList,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'student_enrollment_map' => ['nullable', 'array'],
            'student_enrollment_map.*' => ['integer', 'exists:enrollments,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $program = Program::findOrFail($validated['program_id']);
        if ($program->type !== 'kelas') {
            return back()->withErrors(['program_id' => 'Program harus bertipe kelas.'])->withInput();
        }

        $sessionDate = Carbon::parse($validated['session_date']);
        $teacherIds = $validated['teacher_ids'] ?? [];
        $studentEnrollmentMap = $validated['student_enrollment_map'] ?? [];

        if (empty($studentEnrollmentMap)) {
            return back()->withErrors(['student_enrollment_map' => 'Pilih minimal 1 murid yang hadir.'])->withInput();
        }

        DB::transaction(function () use ($validated, $sessionDate, $teacherIds, $studentEnrollmentMap, $request) {
            $classSession = ClassSession::create([
                'program_id' => $validated['program_id'],
                'session_date' => $sessionDate,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($teacherIds)) {
                $classSession->teachers()->attach($teacherIds);
            }

            foreach ($studentEnrollmentMap as $enrollmentId) {
                $enrollment = Enrollment::with(['students'])->withTrashed()->find($enrollmentId);
                if (!$enrollment || $enrollment->trashed()) {
                    continue;
                }

                // kelas: exactly 1 student per enrollment
                $student = $enrollment->students->first();
                if (!$student) {
                    continue;
                }

                $attendance = MonthlyAttendance::create([
                    'enrollment_id' => $enrollmentId,
                    'class_session_id' => $classSession->id,
                    'session_teacher_id' => $teacherIds[0] ?? null,
                    'lesson_date' => $sessionDate,
                    'month' => $sessionDate->month,
                    'year' => $sessionDate->year,
                    'status_validation' => 'pending',
                    'parent_rate' => $enrollment->getParentRateForCount(1),
                    'teacher_rate' => $enrollment->getTeacherRateForCount(1),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $attendance->students()->sync([$student->id => ['total_present' => 1]]);
                $enrollment->update(['validation_status' => 1]);
            }
        });

        return redirect()
            ->route('admin.class-student-sessions.index', ['month' => $sessionDate->month, 'year' => $sessionDate->year])
            ->with('status', 'Presensi kelas berhasil dicatat.');
    }

    public function edit(Request $request, ClassSession $session): View
    {
        $programs = Program::where('type', 'kelas')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $session->load(['program', 'teachers', 'attendances.enrollment', 'attendances.students']);

        // Load students from ALL programs (for JS program switching), include program_id
        $allEnrollments = Enrollment::with(['students'])
            ->whereIn('program_id', $programs->pluck('id'))
            ->where('type', 'kelas')
            ->where('status', 'active')
            ->withTrashed()
            ->get();

        $allStudents = [];
        foreach ($allEnrollments as $enrollment) {
            foreach ($enrollment->students as $student) {
                $allStudents[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->display_name,
                    'enrollment_id' => $enrollment->id,
                    'program_id' => $enrollment->program_id,
                ];
            }
        }

        // Build { student_id => enrollment_id } map correctly from attendances
        $existingEnrollmentMap = [];
        foreach ($session->attendances as $attendance) {
            foreach ($attendance->students as $student) {
                $existingEnrollmentMap[$student->id] = $attendance->enrollment_id;
            }
        }

        $existingStudentIds = array_keys($existingEnrollmentMap);

        $teachers = Teacher::orderBy('name')->get(['id', 'name']);
        $teachersList = $teachers
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->toArray();

        $sessionTeachers = $session->teachers
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->toArray();

        return view('admin.class-student-sessions.edit', [
            'programs' => $programs,
            'session' => $session,
            'allStudents' => $allStudents,
            'teachers' => $teachers,
            'teachersList' => $teachersList,
            'month' => $session->session_date->month,
            'year' => $session->session_date->year,
            'existingStudentIds' => $existingStudentIds,
            'existingEnrollmentMap' => $existingEnrollmentMap,
            'sessionTeachers' => $sessionTeachers,
        ]);
    }

    public function update(Request $request, ClassSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'student_enrollment_map' => ['nullable', 'array'],
            'student_enrollment_map.*' => ['integer', 'exists:enrollments,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $program = Program::findOrFail($validated['program_id']);
        if ($program->type !== 'kelas') {
            return back()->withErrors(['program_id' => 'Program harus bertipe kelas.'])->withInput();
        }

        $sessionDate = Carbon::parse($validated['session_date']);
        $teacherIds = $validated['teacher_ids'] ?? [];
        $studentEnrollmentMap = $validated['student_enrollment_map'] ?? [];

        if (empty($studentEnrollmentMap)) {
            return back()->withErrors(['student_enrollment_map' => 'Pilih minimal 1 murid yang hadir.'])->withInput();
        }

        DB::transaction(function () use ($validated, $session, $sessionDate, $teacherIds, $studentEnrollmentMap, $request) {
            $session->update([
                'program_id' => $validated['program_id'],
                'session_date' => $sessionDate,
                'notes' => $validated['notes'] ?? null,
            ]);

            $session->teachers()->sync($teacherIds);

            $session->attendances()->delete();

            foreach ($studentEnrollmentMap as $enrollmentId) {
                $enrollment = Enrollment::with(['students'])->withTrashed()->find($enrollmentId);
                if (!$enrollment || $enrollment->trashed()) {
                    continue;
                }

                $student = $enrollment->students->first();
                if (!$student) {
                    continue;
                }

                $attendance = MonthlyAttendance::create([
                    'enrollment_id' => $enrollmentId,
                    'class_session_id' => $session->id,
                    'session_teacher_id' => $teacherIds[0] ?? null,
                    'lesson_date' => $sessionDate,
                    'month' => $sessionDate->month,
                    'year' => $sessionDate->year,
                    'status_validation' => 'pending',
                    'parent_rate' => $enrollment->getParentRateForCount(1),
                    'teacher_rate' => $enrollment->getTeacherRateForCount(1),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $attendance->students()->sync([$student->id => ['total_present' => 1]]);
                $enrollment->update(['validation_status' => 1]);
            }
        });

        return redirect()
            ->route('admin.class-student-sessions.index', ['month' => $sessionDate->month, 'year' => $sessionDate->year])
            ->with('status', 'Presensi kelas berhasil diperbarui.');
    }

    public function destroy(ClassSession $session): RedirectResponse
    {
        $month = $session->session_date->month;
        $year = $session->session_date->year;

        $session->attendances()->delete();
        $session->delete();

        return redirect()
            ->route('admin.class-student-sessions.index', ['month' => $month, 'year' => $year])
            ->with('status', 'Sesi kelas berhasil dihapus.');
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
