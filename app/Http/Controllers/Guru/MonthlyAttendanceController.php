<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $this->resolveTeacher($request);

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'students',
        ])
            ->whereHas('enrollment', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->latest()
            ->get();

        return view('guru.presensi.index', compact('attendances'));
    }

    public function create(Request $request): View
    {
        $teacher = $this->resolveTeacher($request);

        $enrollments = Enrollment::with(['program', 'students'])
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return view('guru.presensi.create', compact('enrollments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'lesson_date' => ['required', 'date', 'before_or_equal:today'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $enrollment = Enrollment::with(['students', 'program'])
            ->where('id', $validated['enrollment_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $isClassProgram = $enrollment->program?->type === 'kelompok';

        $lessonDate = Carbon::parse($validated['lesson_date']);
        $daysSinceLesson = $lessonDate->diffInDays(now(), false);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $teacherSlug = str_replace(' ', '_', strtolower($teacher->name));
            $lessonDateStr = $lessonDate->format('Y-m-d');
            $extension = $file->getClientOriginalExtension();
            $imagePath = sprintf('photo/attendance/%s/%d_%s.%s', $teacherSlug, $enrollment->id, $lessonDateStr, $extension);
            $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
        }

        // Auto-determine status: terima if within 7 days, terlambat if over 7 days
        $status = $daysSinceLesson <= 7 ? 'terima' : 'terlambat';

        if ($isClassProgram) {
            // CLASS: Guru only marks session happened, no student selection
            // teacher_rate = class_rate (flat per session)
            // parent_rate = from enrollment (for billing when admin fills students)
            $teacherRate = (int) ($teacher->class_rate ?? 0);
            $parentRate = (int) $enrollment->parent_rate;

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

            $message = $status === 'terima'
                ? 'Presensi diterima (' . $presentCount . ' murid hadir, rate ortu Rp' . number_format($parentRate) . ', rate guru Rp' . number_format($teacherRate) . ').'
                : 'Presensi terlambat (lebih dari 7 hari). Guru akan mendapat potongan 10%.';
        }

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', $message);
    }

    public function edit(Request $request, MonthlyAttendance $attendance): View
    {
        $teacher = $this->resolveTeacher($request);

        abort_unless($attendance->enrollment?->teacher_id === $teacher->id, 403);

        if ($attendance->status_validation === 'terima') {
            abort(403);
        }

        $attendance->load(['enrollment.students', 'students']);

        return view('guru.presensi.edit', compact('attendance'));
    }

    public function update(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        abort_unless($attendance->enrollment?->teacher_id === $teacher->id, 403);

        if ($attendance->status_validation === 'terima') {
            abort(403);
        }

        $validated = $request->validate([
            'lesson_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $lessonDate = Carbon::parse($validated['lesson_date']);
        $daysSinceLesson = $lessonDate->diffInDays(now(), false);

        $updateData = [
            'lesson_date' => $lessonDate,
            'month' => $lessonDate->month,
            'year' => $lessonDate->year,
            'notes' => $validated['notes'] ?? null,
            'status_validation' => $daysSinceLesson <= 7 ? 'terima' : 'terlambat',
        ];

        // Handle image upload (replace old if exists)
        if ($request->hasFile('image')) {
            if ($attendance->image) {
                Storage::disk('public')->delete($attendance->image);
            }
            $file = $request->file('image');
            $teacherSlug = str_replace(' ', '_', strtolower($teacher->name));
            $lessonDateStr = $lessonDate->format('Y-m-d');
            $extension = $file->getClientOriginalExtension();
            $imagePath = sprintf('photo/attendance/%s/%d_%s.%s', $teacherSlug, $attendance->enrollment_id, $lessonDateStr, $extension);
            $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
            $updateData['image'] = $imagePath;
        }

        $attendance->update($updateData);

        // All students are marked as present (teacher fills attendance only when student attends)
        $attendance->load('enrollment.students');
        $attendance->students()->sync(
            $attendance->enrollment->students->mapWithKeys(fn ($student) => [$student->id => ['total_present' => 1]])
        );

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', 'Presensi diperbarui.');
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
