<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function __construct(private AttendanceFineService $fineService) {}
    public function index(Request $request): View
    {
        $teacher = $this->resolveTeacher($request);

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'students',
        ])
            ->whereHas('enrollment', fn ($q) => $q
                ->where('teacher_id', $teacher->id)
                ->where('type', 'privat'))
            ->latest()
            ->get();

        return view('guru.presensi.index', compact('attendances'));
    }

    public function create(Request $request): View
    {
        $teacher = $this->resolveTeacher($request);

        $enrollments = Enrollment::with(['program', 'students'])
            ->where('teacher_id', $teacher->id)
            ->where('type', 'privat')
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $billingMode = $this->fineService->getBillingMode();

        if ($billingMode === 'monthly') {
            return view('guru.presensi.create-monthly', compact('enrollments', 'billingMode'));
        }

        return view('guru.presensi.create', compact('enrollments', 'billingMode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'lesson_date' => [
                'required',
                'date',
                'before_or_equal:today',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = \DB::table('enrollment_attendances')
                        ->where('enrollment_id', $request->input('enrollment_id'))
                        ->whereDate('lesson_date', Carbon::parse($value)->toDateString())
                        ->exists();
                    if ($exists) {
                        $fail('Presensi untuk enrollment dan tanggal ini sudah ada.');
                    }
                },
            ],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $enrollment = Enrollment::with(['students', 'program'])
            ->where('id', $validated['enrollment_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $isClassProgram = $enrollment->isKelas();

        $lessonDate = Carbon::parse($validated['lesson_date']);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $teacherSlug = str_replace(' ', '_', strtolower($teacher->full_name ?? 'guru_' . $teacher->id));
            $lessonDateStr = $lessonDate->format('Y-m-d');
            $extension = $file->getClientOriginalExtension();
            $imagePath = sprintf('photo/attendance/%s/%d_%s.%s', $teacherSlug, $enrollment->id, $lessonDateStr, $extension);
            $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
        }

        // Monthly billing: always accept. Daily billing: >3 days = late.
        $status = $this->fineService->isBillingModeMonthly()
            ? 'terima'
            : ($lessonDate->diffInDays(now(), false) <= 3 ? 'terima' : 'terlambat');

        if ($isClassProgram) {
            // CLASS: Guru only marks session happened, no student selection
            // teacher_rate = from enrollment (per-session rate for teacher)
            // parent_rate = from enrollment (for billing when admin fills students)
            $teacherRate = (int) ($enrollment->teacher_rate ?? 0);
            $parentRate = (int) $enrollment->parent_rate;

            $attendance = MonthlyAttendance::create([
                'enrollment_id' => $enrollment->id,
                'session_teacher_id' => $teacher->id,
                'lesson_date' => $lessonDate,
                'month' => $lessonDate->month,
                'year' => $lessonDate->year,
                'notes' => $validated['notes'] ?? null,
                'image' => $imagePath,
                'status_validation' => $status,
                'parent_rate' => $parentRate,
                'teacher_rate' => $teacherRate,
                'created_by' => $request->user()->id,
            ]);

            // NO students synced yet - admin will fill them later
            $enrollment->update(['validation_status' => 1]);

            $message = 'Presensi kelas berhasil dicatat. Admin akan mengisi daftar murid yang hadir.';
        } else {
            // PRIVATE: Teacher selects which students attended
            if (empty($validated['student_ids'])) {
                return back()->withErrors(['student_ids' => 'Pilih minimal 1 murid yang hadir.'])->withInput();
            }

            // Calculate rates based on number of present students
            $presentCount = count($validated['student_ids']);
            $parentRate = $enrollment->getParentRateForCount($presentCount);
            $teacherRate = $enrollment->getTeacherRateForCount($presentCount);

            $attendance = MonthlyAttendance::create([
                'enrollment_id' => $enrollment->id,
                'lesson_date' => $lessonDate,
                'month' => $lessonDate->month,
                'year' => $lessonDate->year,
                'notes' => $validated['notes'] ?? null,
                'image' => $imagePath,
                'status_validation' => $status,
                'parent_rate' => $parentRate,
                'teacher_rate' => $teacherRate,
                'created_by' => $request->user()->id,
            ]);

            // Only mark checked students as present
            $attendance->students()->sync(
                collect($validated['student_ids'])->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
            );

            $enrollment->update(['validation_status' => 1]);

            $message = $status === 'terlambat'
                ? 'Presensi berhasil dicatat (terlambat — lebih dari 3 hari sejak tanggal les).'
                : 'Presensi berhasil dicatat.';
        }

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', $message);
    }

    public function edit(Request $request, MonthlyAttendance $attendance): View
    {
        $teacher = $this->resolveTeacher($request);

        // Allow access if teacher owns the enrollment (privat) or is the session teacher (kelas)
        $isOwner = $attendance->enrollment?->teacher_id === $teacher->id;
        $isSessionTeacher = $attendance->session_teacher_id === $teacher->id;
        abort_unless($isOwner || $isSessionTeacher, 403);

        if ($attendance->status_validation === 'ditolak') {
            abort(403);
        }

        $enrollments = Enrollment::with(['program', 'students'])
            ->where('teacher_id', $teacher->id)
            ->where('type', 'privat')
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $attendance->load(['enrollment.students', 'students']);

        return view('guru.presensi.edit', compact('attendance', 'enrollments'));
    }

    public function update(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        // Allow access if teacher owns the enrollment (privat) or is the session teacher (kelas)
        $isOwner = $attendance->enrollment?->teacher_id === $teacher->id;
        $isSessionTeacher = $attendance->session_teacher_id === $teacher->id;
        abort_unless($isOwner || $isSessionTeacher, 403);

        if ($attendance->status_validation === 'ditolak') {
            abort(403);
        }

        $validated = $request->validate([
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'lesson_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $newEnrollment = Enrollment::with(['students', 'program'])
            ->where('id', $validated['enrollment_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $isClassProgram = $newEnrollment->isKelas();

        $lessonDate = Carbon::parse($validated['lesson_date']);

        // Duplicate check using the new enrollment_id
        $exists = \DB::table('enrollment_attendances')
            ->where('enrollment_id', $newEnrollment->id)
            ->whereDate('lesson_date', $lessonDate->toDateString())
            ->where('id', '!=', $attendance->id)
            ->exists();
        if ($exists) {
            return back()
                ->withErrors(['lesson_date' => 'Presensi untuk enrollment dan tanggal ini sudah ada.'])
                ->withInput();
        }

        // Monthly billing: always accept. Daily billing: >3 days = late.
        $status = $this->fineService->isBillingModeMonthly()
            ? 'terima'
            : ($lessonDate->diffInDays(now(), false) <= 3 ? 'terima' : 'terlambat');

        $isEnrollmentChanged = $attendance->enrollment_id !== $newEnrollment->id;

        $updateData = [
            'enrollment_id' => $newEnrollment->id,
            'lesson_date' => $lessonDate,
            'month' => $lessonDate->month,
            'year' => $lessonDate->year,
            'notes' => $validated['notes'] ?? null,
            'status_validation' => $status,
        ];

        // Recalculate rates if enrollment changed or student list may change
        if ($isEnrollmentChanged || array_key_exists('student_ids', $validated)) {
            $studentIds = $validated['student_ids'] ?? [];
            $presentCount = ! empty($studentIds) ? count($studentIds) : $attendance->students()->count();
            $updateData['parent_rate'] = $newEnrollment->getParentRateForCount($presentCount);
            $updateData['teacher_rate'] = $newEnrollment->getTeacherRateForCount($presentCount);
        }

        // Handle image upload (replace old if exists)
        if ($request->hasFile('image')) {
            if ($attendance->image) {
                Storage::disk('public')->delete($attendance->image);
            }
            $file = $request->file('image');
            $teacherSlug = str_replace(' ', '_', strtolower($teacher->full_name ?? 'guru_' . $teacher->id));
            $lessonDateStr = $lessonDate->format('Y-m-d');
            $extension = $file->getClientOriginalExtension();
            $imagePath = sprintf('photo/attendance/%s/%d_%s.%s', $teacherSlug, $newEnrollment->id, $lessonDateStr, $extension);
            $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
            $updateData['image'] = $imagePath;
        }

        $attendance->update($updateData);

        // When enrollment changed, reload enrollment to get fresh students list.
        // When enrollment unchanged, only sync if student_ids explicitly sent.
        if ($isEnrollmentChanged) {
            $checkedIds = $validated['student_ids'] ?? [];
            $newStudentIds = $newEnrollment->students->pluck('id')->toArray();
            if (! empty($checkedIds)) {
                // Only include students that exist in the new enrollment
                $syncIds = array_intersect($checkedIds, $newStudentIds);
            } else {
                // No explicit selection: preselect all students from new enrollment
                $syncIds = $newStudentIds;
            }
            if (! empty($syncIds)) {
                $attendance->students()->sync(
                    collect($syncIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                );
            } else {
                $attendance->students()->detach();
            }
        } elseif (array_key_exists('student_ids', $validated)) {
            $studentIds = $validated['student_ids'] ?? [];
            if (! empty($studentIds)) {
                $attendance->students()->sync(
                    collect($studentIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                );
            } else {
                $attendance->students()->detach();
            }
        }

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', 'Presensi diperbarui.');
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'sessions' => ['required', 'array', 'min:1', 'max:31'],
            'sessions.*.lesson_date' => ['required', 'date'],
            'sessions.*.student_ids' => ['nullable', 'array'],
            'sessions.*.student_ids.*' => ['integer', 'exists:students,id'],
            'sessions.*.notes' => ['nullable', 'string', 'max:1000'],
            'sessions.*.image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $enrollment = Enrollment::with(['students', 'program'])
            ->where('id', $validated['enrollment_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $isClassProgram = $enrollment->isKelas();

        $createdCount = 0;
        $errors = [];

        \DB::transaction(function () use ($validated, $enrollment, $teacher, $request, $isClassProgram, &$createdCount, &$errors) {
            foreach ($validated['sessions'] as $idx => $session) {
                $lessonDate = Carbon::parse($session['lesson_date']);
                $dateKey = $lessonDate->format('Y-m-d');

                // Check duplicate
                if (\DB::table('enrollment_attendances')
                    ->where('enrollment_id', $enrollment->id)
                    ->whereDate('lesson_date', $dateKey)
                    ->exists()) {
                    $errors[] = "Tab " . ($idx + 1) . ": Presensi untuk {$dateKey} sudah ada.";
                    continue;
                }

                // Monthly billing: always accept. Daily billing: >3 days = late.
                $status = $this->fineService->isBillingModeMonthly()
                    ? 'terima'
                    : ($lessonDate->diffInDays(now(), false) <= 3 ? 'terima' : 'terlambat');

                // Image upload
                $imagePath = null;
                if ($request->hasFile("sessions.{$idx}.image")) {
                    $file = $request->file("sessions.{$idx}.image");
                    $teacherSlug = str_replace(' ', '_', strtolower($teacher->full_name ?? 'guru_' . $teacher->id));
                    $extension = $file->getClientOriginalExtension();
                    $imagePath = sprintf(
                        'photo/attendance/%s/%d_%s_%d.%s',
                        $teacherSlug,
                        $enrollment->id,
                        $dateKey,
                        $idx,
                        $extension
                    );
                    $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
                }

                if ($isClassProgram) {
                    $teacherRate = (int) ($enrollment->teacher_rate ?? 0);
                    $parentRate = (int) $enrollment->parent_rate;

                    MonthlyAttendance::create([
                        'enrollment_id' => $enrollment->id,
                        'session_teacher_id' => $teacher->id,
                        'lesson_date' => $lessonDate,
                        'month' => $lessonDate->month,
                        'year' => $lessonDate->year,
                        'notes' => $session['notes'] ?? null,
                        'image' => $imagePath,
                        'status_validation' => $status,
                        'parent_rate' => $parentRate,
                        'teacher_rate' => $teacherRate,
                        'created_by' => $request->user()->id,
                    ]);

                    $enrollment->updateQuietly(['validation_status' => 1]);
                    $createdCount++;
                } else {
                    $studentIds = $session['student_ids'] ?? [];
                    if (empty($studentIds)) {
                        $errors[] = "Tab " . ($idx + 1) . ": Pilih minimal 1 murid yang hadir.";
                        continue;
                    }

                    $presentCount = count($studentIds);
                    $parentRate = $enrollment->getParentRateForCount($presentCount);
                    $teacherRate = $enrollment->getTeacherRateForCount($presentCount);

                    $attendance = MonthlyAttendance::create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_date' => $lessonDate,
                        'month' => $lessonDate->month,
                        'year' => $lessonDate->year,
                        'notes' => $session['notes'] ?? null,
                        'image' => $imagePath,
                        'status_validation' => $status,
                        'parent_rate' => $parentRate,
                        'teacher_rate' => $teacherRate,
                        'created_by' => $request->user()->id,
                    ]);

                    $attendance->students()->sync(
                        collect($studentIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                    );

                    $enrollment->updateQuietly(['validation_status' => 1]);
                    $createdCount++;
                }
            }
        });

        if (! empty($errors)) {
            return back()->withErrors(['sessions' => implode(' ', $errors)])->withInput();
        }

        $message = $createdCount === 1
            ? 'Presensi berhasil dicatat.'
            : "{$createdCount} presensi berhasil dicatat sekaligus.";

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', $message);
    }

    public function destroy(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        $isOwner = $attendance->enrollment?->teacher_id === $teacher->id;
        $isSessionTeacher = $attendance->session_teacher_id === $teacher->id;
        abort_unless($isOwner || $isSessionTeacher, 403);

        if ($attendance->status_validation === 'ditolak') {
            abort(403);
        }

        if ($attendance->image) {
            Storage::disk('public')->delete($attendance->image);
        }

        $attendance->delete();

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', 'Presensi berhasil dihapus.');
    }

    private function resolveTeacher(Request $request): Teacher
    {
        $teacher = Teacher::query()
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless((bool) $teacher, 403);

        return $teacher;
    }
}
