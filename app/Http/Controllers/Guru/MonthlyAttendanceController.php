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
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'student_totals' => ['required', 'array'],
            'student_totals.*' => ['required', 'integer', 'min:0', 'max:1'],
        ]);

        $enrollment = Enrollment::with(['students', 'program'])
            ->where('id', $validated['enrollment_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $lessonDate = Carbon::parse($validated['lesson_date']);
        $daysSinceLesson = $lessonDate->diffInDays(now(), false);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('presensi', 'public');
        }

        // Auto-determine status: terima if within 7 days, terlambat if over 7 days
        $status = $daysSinceLesson <= 7 ? 'terima' : 'terlambat';

        $attendance = MonthlyAttendance::create([
            'enrollment_id' => $enrollment->id,
            'lesson_date' => $lessonDate,
            'month' => $lessonDate->month,
            'year' => $lessonDate->year,
            'notes' => $validated['notes'] ?? null,
            'image' => $imagePath,
            'status_validation' => $status,
            'created_by' => $request->user()->id,
        ]);

        $attendance->students()->sync(
            collect($validated['student_totals'])
                ->mapWithKeys(fn ($total, $studentId) => [(int) $studentId => ['total_present' => (int) $total]])
                ->all()
        );

        $enrollment->update(['validation_status' => 1]);

        $message = $status === 'terima'
            ? 'Presensi diterima (dalam 7 hari).'
            : 'Presensi terlambat (lebih dari 7 hari). Guru akan mendapat potongan 10%.';

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
            'student_totals' => ['required', 'array'],
            'student_totals.*' => ['required', 'integer', 'min:0', 'max:1'],
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
            $updateData['image'] = $request->file('image')->store('presensi', 'public');
        }

        $attendance->update($updateData);

        $attendance->load('enrollment.students');
        $studentTotals = collect($validated['student_totals']);

        $attendance->students()->sync(
            $studentTotals
                ->mapWithKeys(fn ($total, $studentId) => [(int) $studentId => ['total_present' => (int) $total]])
                ->all()
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
