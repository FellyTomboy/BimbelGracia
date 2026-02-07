<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AttendanceWindow;
use App\Models\Lesson;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $this->resolveTeacher($request);
        $openWindow = $this->currentWindow();

        $attendances = MonthlyAttendance::with(['lesson', 'student'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();

        return view('guru.presensi.index', compact('attendances', 'openWindow'));
    }

    public function create(Request $request): View
    {
        $teacher = $this->resolveTeacher($request);
        $openWindow = $this->currentWindow();

        if (! $openWindow) {
            return view('guru.presensi.closed');
        }

        $lessons = Lesson::with('student')
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        return view('guru.presensi.create', compact('openWindow', 'lessons'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);
        $openWindow = $this->currentWindow();

        if (! $openWindow) {
            return redirect()
                ->route('guru.presensi.index')
                ->withErrors(['period' => 'Periode presensi belum dibuka admin.']);
        }

        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'dates' => ['nullable', 'array'],
            'dates.*' => ['integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string'],
            'total_lessons' => ['required', 'integer', 'min:0'],
        ]);

        $lesson = Lesson::query()
            ->where('id', $validated['lesson_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $exists = MonthlyAttendance::query()
            ->where('lesson_id', $lesson->id)
            ->where('month', $openWindow->month)
            ->where('year', $openWindow->year)
            ->exists();

        if ($exists) {
            $lesson->update(['validation_status' => 2]);

            return back()
                ->withInput()
                ->withErrors(['lesson_id' => 'Presensi untuk periode ini sudah ada.']);
        }

        $dates = collect($validated['dates'] ?? [])
            ->unique()
            ->values()
            ->all();

        MonthlyAttendance::create([
            'lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
            'student_id' => $lesson->student_id,
            'month' => $openWindow->month,
            'year' => $openWindow->year,
            'dates' => $dates,
            'notes' => $validated['notes'] ?? null,
            'total_lessons' => $validated['total_lessons'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $lesson->update(['validation_status' => 1]);

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', 'Presensi dikirim, menunggu validasi admin.');
    }

    public function edit(Request $request, MonthlyAttendance $attendance): View
    {
        $teacher = $this->resolveTeacher($request);

        abort_unless($attendance->teacher_id === $teacher->id, 403);

        if ($attendance->status === 'validated') {
            abort(403);
        }

        return view('guru.presensi.edit', compact('attendance'));
    }

    public function update(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        abort_unless($attendance->teacher_id === $teacher->id, 403);

        if ($attendance->status === 'validated') {
            abort(403);
        }

        $validated = $request->validate([
            'dates' => ['nullable', 'array'],
            'dates.*' => ['integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string'],
            'total_lessons' => ['required', 'integer', 'min:0'],
        ]);

        $dates = collect($validated['dates'] ?? [])
            ->unique()
            ->values()
            ->all();

        $attendance->update([
            'dates' => $dates,
            'notes' => $validated['notes'] ?? null,
            'total_lessons' => $validated['total_lessons'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', 'Presensi diperbarui dan dikirim ulang.');
    }

    private function resolveTeacher(Request $request): Teacher
    {
        $teacher = Teacher::query()
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($teacher, 403);

        return $teacher;
    }

    private function currentWindow(): ?AttendanceWindow
    {
        return AttendanceWindow::query()
            ->where('is_open', true)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }
}
