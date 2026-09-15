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
use Illuminate\Http\JsonResponse;
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
        $programId = (int) $request->query('program_id', 0);

        $sessionsQuery = ClassSession::with(['program', 'teachers', 'attendances.enrollment', 'attendances.students'])
            ->whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->orderBy('session_date');

        if ($programId > 0) {
            $sessionsQuery->where('program_id', $programId);
        }

        $sessions = $sessionsQuery->get()->groupBy(fn ($s) => $s->program_id);

        return view('admin.class-student-sessions.calendar', [
            'month' => $month,
            'year' => $year,
            'start' => $start,
            'daysInMonth' => $start->daysInMonth,
            'firstDayOfWeek' => $start->dayOfWeekIso,
            'sessions' => $sessions,
            'programs' => Program::where('type', 'kelas')->where('status', 'active')->orderBy('name')->get(),
            'selectedProgramId' => $programId ?: null,
            'programPalette' => [
                // Only "Kelas" programs shown in calendar
                3  => ['bg' => '#f5e6d3', 'border' => '#e0c9a6', 'chip_border' => '#8b6914', 'dot' => '#6b4f12'],  // coklat
                5  => ['bg' => '#dbeafe', 'border' => '#93c5fd', 'chip_border' => '#3b82f6', 'dot' => '#2563eb'],      // biru
                6  => ['bg' => '#ede9fe', 'border' => '#c4b5fd', 'chip_border' => '#8b5cf6', 'dot' => '#7c3aed'],   // ungu
                8  => ['bg' => '#fef9c3', 'border' => '#fde047', 'chip_border' => '#eab308', 'dot' => '#ca8a04'],   // kuning
            ],
        ]);
    }

    public function table(Request $request): View
    {
        // Load ClassSession grouped by program+date, with all related data
        $sessions = ClassSession::with([
            'program',
            'teachers',
            'attendances.enrollment',
            'attendances.students',
        ])
            ->whereHas('program', fn ($q) => $q->where('type', 'kelas'))
            ->orderByDesc('session_date')
            ->get();

        // Group by date, then by program
        $grouped = [];
        foreach ($sessions as $session) {
            $dateKey = $session->session_date->format('Y-m-d');
            $programKey = $session->program_id;
            $groupKey = "{$dateKey}_{$programKey}";

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'date' => $session->session_date,
                    'program' => $session->program,
                    'class_session' => $session,
                    'teachers' => $session->teachers,
                    'students' => collect(),
                    'sessions' => collect(),
                ];
            }

            foreach ($session->attendances as $attendance) {
                foreach ($attendance->students as $student) {
                    if (!$grouped[$groupKey]['students']->contains('id', $student->id)) {
                        $grouped[$groupKey]['students']->push($student);
                    }
                }
            }
            $grouped[$groupKey]['sessions']->push($session);
        }

        return view('admin.class-student-sessions.table', [
            'grouped' => array_values($grouped),
        ]);
    }

    public function create(Request $request): View
    {
        $month = (int) ($request->query('month') ?? now()->month);
        $year = (int) ($request->query('year') ?? now()->year);
        $programId = (int) ($request->query('program_id') ?? 0);
        $sessionDate = $request->query('session_date')
            ? Carbon::parse($request->query('session_date'))->format('Y-m-d')
            : Carbon::create($year, $month, 1)->format('Y-m-d');

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

        // Teachers assigned to the selected program (with pivot rate)
        $programTeachers = $selectedProgram
            ? $selectedProgram->teachers()->orderBy('full_name')->get()
            : collect();

        $teachersList = $programTeachers
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->displayName,
                'rate' => $t->pivot->rate,
            ])
            ->values()
            ->toArray();

        // All teachers grouped by program (for JS dynamic switching)
        $teachersByProgram = [];
        foreach ($programs as $p) {
            $pTeachers = $p->teachers()->orderBy('full_name')->get();
            $teachersByProgram[$p->id] = $pTeachers
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->displayName,
                    'rate' => $t->pivot->rate,
                ])
                ->values()
                ->toArray();
        }

        return view('admin.class-student-sessions.create', [
            'programs' => $programs,
            'selectedProgram' => $selectedProgram,
            'enrollments' => $enrollments,
            'allStudents' => $allStudents,
            'teachersList' => $teachersList,
            'teachersByProgram' => $teachersByProgram,
            'month' => $month,
            'year' => $year,
            'sessionDate' => $sessionDate,
        ]);
    }

    public function createForm(Request $request): JsonResponse
    {
        $month = (int) ($request->query('month') ?? now()->month);
        $year = (int) ($request->query('year') ?? now()->year);
        $programId = (int) ($request->query('program_id') ?? 0);
        $sessionDate = $request->query('session_date')
            ? Carbon::parse($request->query('session_date'))->format('Y-m-d')
            : Carbon::create($year, $month, 1)->format('Y-m-d');

        $programs = Program::where('type', 'kelas')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // All students grouped by program_id
        $enrollments = Enrollment::with(['students'])
            ->whereIn('program_id', $programs->pluck('id'))
            ->where('type', 'kelas')
            ->where('status', 'active')
            ->withTrashed()
            ->get();

        $studentsByProgram = [];
        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->students as $student) {
                $pid = (int) $enrollment->program_id;
                if (!isset($studentsByProgram[$pid])) {
                    $studentsByProgram[$pid] = [];
                }
                $studentsByProgram[$pid][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->display_name,
                    'enrollment_id' => $enrollment->id,
                ];
            }
        }

        // Teachers grouped by program_id
        $teachersByProgram = [];
        foreach ($programs as $p) {
            $teachersByProgram[(int) $p->id] = $p->teachers()
                ->orderBy('full_name')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->displayName,
                    'rate' => $t->pivot->rate ?? 0,
                ])
                ->values()
                ->toArray();
        }

        $html = view('admin.class-student-sessions._form', [
            'session' => null,
            'programs' => $programs,
            'sessionDate' => $sessionDate,
            'selectedProgramId' => $programId ?: null,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Tambah Presensi Kelas',
            'teachersByProgram' => $teachersByProgram,
            'studentsByProgram' => $studentsByProgram,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'student_enrollment_map' => ['nullable', 'array'],
            'student_enrollment_map.*' => ['integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $program = Program::findOrFail($validated['program_id']);
        if ($program->type !== 'kelas') {
            return back()->withErrors(['program_id' => 'Program harus bertipe kelas.'])->withInput();
        }

        $sessionDate = Carbon::parse($validated['session_date']);
        $teacherIds = $validated['teacher_ids'] ?? [];
        $studentEnrollmentMap = $validated['student_enrollment_map'] ?? [];

        // Validate enrollment IDs exist (including soft-deleted)
        foreach ($studentEnrollmentMap as $enrollmentId) {
            $enrollment = Enrollment::withTrashed()->find($enrollmentId);
            if (!$enrollment || $enrollment->trashed()) {
                return back()->withErrors(['student_enrollment_map' => 'Enrollment tidak valid.'])->withInput();
            }
        }

        DB::transaction(function () use ($validated, $sessionDate, $teacherIds, $studentEnrollmentMap, $request) {
            $classSession = ClassSession::create([
                'program_id' => $validated['program_id'],
                'session_date' => $sessionDate,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($teacherIds)) {
                foreach ($teacherIds as $teacherId) {
                    $rate = $classSession->program
                        ->teachers()
                        ->where('teachers.id', $teacherId)
                        ->first()
                        ?->pivot
                        ?->rate ?? 0;
                    $classSession->teachers()->attach($teacherId, ['rate' => $rate]);
                }
            }

            // Create MonthlyAttendance per enrollment (student selected), OR per teacher (teacher selected, no students yet)
            if (!empty($studentEnrollmentMap)) {
                foreach ($studentEnrollmentMap as $index => $enrollmentId) {
                    $enrollment = Enrollment::with(['students'])->withTrashed()->find($enrollmentId);
                    if (!$enrollment || $enrollment->trashed()) {
                        continue;
                    }

                    $student = $enrollment->students->first();
                    if (!$student) {
                        continue;
                    }

                    // Pair teacher by index: enrollment[0]→teacher[0], enrollment[1]→teacher[1], etc.
                    // Falls back to last teacher, then first teacher, then null
                    $teacherId = $teacherIds[$index]
                        ?? ($teacherIds[array_key_last($teacherIds)] ?? ($teacherIds[0] ?? null));

                    $attendance = MonthlyAttendance::create([
                        'enrollment_id' => $enrollmentId,
                        'class_session_id' => $classSession->id,
                        'session_teacher_id' => $teacherId,
                        'lesson_date' => $sessionDate,
                        'month' => $sessionDate->month,
                        'year' => $sessionDate->year,
                        'status_validation' => 'terima',
                        'parent_rate' => $enrollment->getParentRateForCount(1),
                        'teacher_rate' => $classSession->teachers()->where('teachers.id', $teacherId)->first()?->pivot?->rate ?? 0,
                        'agreed_sessions_per_month' => $enrollment->agreed_sessions_per_month,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);

                    $attendance->students()->sync([$student->id => ['total_present' => 1]]);
                    $enrollment->update(['validation_status' => 1]);
                }
            } elseif (!empty($teacherIds)) {
                // No students selected — create one MonthlyAttendance per teacher (enrollment_id null)
                // so the teacher still appears in WA Guru report
                foreach ($teacherIds as $teacherId) {
                    MonthlyAttendance::create([
                        'enrollment_id' => null,
                        'class_session_id' => $classSession->id,
                        'session_teacher_id' => $teacherId,
                        'lesson_date' => $sessionDate,
                        'month' => $sessionDate->month,
                        'year' => $sessionDate->year,
                        'status_validation' => 'terima',
                        'teacher_rate' => $classSession->teachers()->where('teachers.id', $teacherId)->first()?->pivot?->rate ?? 0,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi kelas berhasil dicatat.',
                'session_id' => $classSession->id,
            ]);
        }

        return redirect()
            ->route('admin.class-student-sessions.index', ['month' => $sessionDate->month, 'year' => $sessionDate->year])
            ->with('status', 'Presensi kelas berhasil dicatat.');
    }

    public function editForm(Request $request, ClassSession $session): JsonResponse
    {
        $programs = Program::where('type', 'kelas')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $session->load(['program', 'teachers', 'attendances.enrollment', 'attendances.students']);

        // Build { student_id => enrollment_id } map correctly from attendances
        $existingEnrollmentMap = [];
        foreach ($session->attendances as $attendance) {
            foreach ($attendance->students as $student) {
                $existingEnrollmentMap[$student->id] = $attendance->enrollment_id;
            }
        }
        $existingStudentIds = array_keys($existingEnrollmentMap);

        // Teachers from the session
        $sessionTeachers = $session->teachers
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->displayName,
                'rate' => $t->pivot->rate ?? 0,
            ])
            ->values()
            ->toArray();

        // All teachers grouped by program
        $teachersByProgram = [];
        foreach ($programs as $p) {
            $teachersByProgram[(int) $p->id] = $p->teachers()
                ->orderBy('full_name')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->displayName,
                    'rate' => $t->pivot->rate ?? 0,
                ])
                ->values()
                ->toArray();
        }

        // All students grouped by program
        $enrollments = Enrollment::with(['students'])
            ->whereIn('program_id', $programs->pluck('id'))
            ->where('type', 'kelas')
            ->where('status', 'active')
            ->withTrashed()
            ->get();

        $studentsByProgram = [];
        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->students as $student) {
                $pid = (int) $enrollment->program_id;
                if (!isset($studentsByProgram[$pid])) {
                    $studentsByProgram[$pid] = [];
                }
                $studentsByProgram[$pid][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->display_name,
                    'enrollment_id' => $enrollment->id,
                ];
            }
        }

        $html = view('admin.class-student-sessions._form', [
            'session' => $session,
            'programs' => $programs,
            'sessionDate' => $session->session_date->format('Y-m-d'),
            'selectedProgramId' => $session->program_id,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Edit Presensi Kelas — '.$session->session_date->format('d/m/Y'),
            'teachersByProgram' => $teachersByProgram,
            'studentsByProgram' => $studentsByProgram,
            'sessionTeachers' => $sessionTeachers,
            'existingStudentIds' => $existingStudentIds,
            'existingEnrollmentMap' => $existingEnrollmentMap,
        ]);
    }

    public function update(Request $request, ClassSession $session): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'student_enrollment_map' => ['nullable', 'array'],
            'student_enrollment_map.*' => ['integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $program = Program::findOrFail($validated['program_id']);
        if ($program->type !== 'kelas') {
            return back()->withErrors(['program_id' => 'Program harus bertipe kelas.'])->withInput();
        }

        $sessionDate = Carbon::parse($validated['session_date']);
        $teacherIds = $validated['teacher_ids'] ?? [];
        $studentEnrollmentMap = $validated['student_enrollment_map'] ?? [];

        // Validate enrollment IDs exist (including soft-deleted)
        foreach ($studentEnrollmentMap as $enrollmentId) {
            $enrollment = Enrollment::withTrashed()->find($enrollmentId);
            if (!$enrollment || $enrollment->trashed()) {
                return back()->withErrors(['student_enrollment_map' => 'Enrollment tidak valid.'])->withInput();
            }
        }

        DB::transaction(function () use ($validated, $session, $sessionDate, $teacherIds, $studentEnrollmentMap, $request) {
            $session->update([
                'program_id' => $validated['program_id'],
                'session_date' => $sessionDate,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Attach teachers with their program rates
            $session->teachers()->detach();
            if (!empty($teacherIds)) {
                $program = $session->fresh()->program;
                foreach ($teacherIds as $teacherId) {
                    $rate = $program
                        ->teachers()
                        ->where('teachers.id', $teacherId)
                        ->first()
                        ?->pivot
                        ?->rate ?? 0;
                    $session->teachers()->attach($teacherId, ['rate' => $rate]);
                }
            }

            // Collect existing MonthlyAttendance records with null enrollment_id (teacher-only records)
            $existingNullEnrollment = $session->attendances()
                ->whereNull('enrollment_id')
                ->get()
                ->keyBy('session_teacher_id');

            // Delete only attendances that have an enrollment_id (student records) — teacher-only records preserved above
            $orphanedEnrollmentIds = $session->attendances()
                ->whereNotNull('enrollment_id')
                ->pluck('enrollment_id')
                ->unique()
                ->diff(collect($studentEnrollmentMap))
                ->toArray(); // capture BEFORE deletion
            $session->attendances()->whereNotNull('enrollment_id')->delete();

            $usedNullEnrollmentIds = [];

            foreach ($studentEnrollmentMap as $index => $enrollmentId) {
                $enrollment = Enrollment::with(['students'])->withTrashed()->find($enrollmentId);
                if (!$enrollment || $enrollment->trashed()) {
                    continue;
                }

                $student = $enrollment->students->first();
                if (!$student) {
                    continue;
                }

                // Pair teacher by index: enrollment[0]→teacher[0], enrollment[1]→teacher[1], etc.
                $teacherId = $teacherIds[$index]
                    ?? ($teacherIds[array_key_last($teacherIds)] ?? ($teacherIds[0] ?? null));

                // Reuse an existing null-enrollment record for this teacher if one exists
                $reuseRecord = $teacherId && isset($existingNullEnrollment[$teacherId]) && !in_array($existingNullEnrollment[$teacherId]->id, $usedNullEnrollmentIds);

                if ($reuseRecord) {
                    $attendance = $existingNullEnrollment[$teacherId];
                    $attendance->update([
                        'enrollment_id' => $enrollmentId,
                        'session_teacher_id' => $teacherId,
                        'lesson_date' => $sessionDate,
                        'month' => $sessionDate->month,
                        'year' => $sessionDate->year,
                        'status_validation' => 'terima',
                        'parent_rate' => $enrollment->getParentRateForCount(1),
                        'teacher_rate' => $session->teachers()->where('teachers.id', $teacherId)->first()?->pivot?->rate ?? 0,
                        'agreed_sessions_per_month' => $enrollment->agreed_sessions_per_month,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);
                    $usedNullEnrollmentIds[] = $attendance->id;
                } else {
                    $attendance = MonthlyAttendance::create([
                        'enrollment_id' => $enrollmentId,
                        'class_session_id' => $session->id,
                        'session_teacher_id' => $teacherId,
                        'lesson_date' => $sessionDate,
                        'month' => $sessionDate->month,
                        'year' => $sessionDate->year,
                        'status_validation' => 'terima',
                        'parent_rate' => $enrollment->getParentRateForCount(1),
                        'teacher_rate' => $session->teachers()->where('teachers.id', $teacherId)->first()?->pivot?->rate ?? 0,
                        'agreed_sessions_per_month' => $enrollment->agreed_sessions_per_month,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);
                }

                $attendance->students()->sync([$student->id => ['total_present' => 1]]);
                $enrollment->update(['validation_status' => 1]);
            }

            // Reset orphaned enrollment validation_status (removed from this session)
            foreach ($orphanedEnrollmentIds as $enrollmentId) {
                if (!Enrollment::find($enrollmentId)?->attendances()->exists()) {
                    Enrollment::where('id', $enrollmentId)->update(['validation_status' => 0]);
                }
            }

            // Remaining unused null-enrollment records: keep them if teacher is still selected, otherwise they're already orphaned via $existingNullEnrollment key diff
            // Delete null-enrollment records whose teacher is no longer selected
            $session->attendances()
                ->whereNull('enrollment_id')
                ->whereNotIn('id', $usedNullEnrollmentIds)
                ->whereNotIn('session_teacher_id', $teacherIds)
                ->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi kelas berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('admin.class-student-sessions.index', ['month' => $sessionDate->month, 'year' => $sessionDate->year])
            ->with('status', 'Presensi kelas berhasil diperbarui.');
    }

    public function previewDeleteConfirm(ClassSession $session): JsonResponse
    {
        $session->load(['program', 'teachers']);
        $html = view('admin.class-student-sessions._delete-confirm', compact('session'))->render();
        return response()->json(['html' => $html, 'title' => 'Hapus Sesi Kelas?']);
    }

    public function destroy(Request $request, ClassSession $session): JsonResponse|RedirectResponse
    {
        $month = $session->session_date->month;
        $year = $session->session_date->year;

        $enrollmentIds = $session->attendances()->pluck('enrollment_id')->unique();
        $session->attendances()->delete();
        $session->delete();

        foreach ($enrollmentIds as $enrollmentId) {
            if (!Enrollment::find($enrollmentId)?->attendances()->exists()) {
                Enrollment::where('id', $enrollmentId)->update(['validation_status' => 0]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Sesi kelas berhasil dihapus.']);
        }

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
