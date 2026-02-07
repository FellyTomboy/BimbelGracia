<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\MonthlyAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function index(): View
    {
        $attendances = MonthlyAttendance::with(['lesson', 'teacher', 'student'])
            ->latest()
            ->get();

        return view('admin.presensi.index', compact('attendances'));
    }

    public function show(MonthlyAttendance $attendance): View
    {
        $attendance->load(['lesson', 'teacher', 'student']);
        $lessons = Lesson::with(['teacher', 'student'])
            ->orderBy('code')
            ->get();

        return view('admin.presensi.show', compact('attendance', 'lessons'));
    }

    public function updateLesson(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
        ]);

        $lesson = Lesson::with(['teacher', 'student'])->findOrFail($validated['lesson_id']);

        $attendance->update([
            'lesson_id' => $lesson->id,
            'teacher_id' => $lesson->teacher_id,
            'student_id' => $lesson->student_id,
            'status' => 'pending',
            'validated_at' => null,
            'validated_by' => null,
        ]);

        return redirect()
            ->route('admin.presensi.show', $attendance)
            ->with('status', 'ID les diperbarui.');
    }

    public function validateAttendance(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:validated,needs_fix'],
        ]);

        $attendance->update([
            'status' => $validated['status'],
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        if ($attendance->lesson && $validated['status'] === 'validated') {
            $attendance->lesson->update(['validation_status' => 1]);
        }

        return redirect()
            ->route('admin.presensi.show', $attendance)
            ->with('status', 'Presensi diperbarui.');
    }
}
