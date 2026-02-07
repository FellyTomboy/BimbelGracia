<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function index(): View
    {
        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->latest()
            ->get();

        return view('admin.presensi.index', compact('attendances'));
    }

    public function show(MonthlyAttendance $attendance): View
    {
        $attendance->load([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ]);
        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get();

        return view('admin.presensi.show', compact('attendance', 'enrollments'));
    }

    public function updateEnrollment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
        ]);

        $enrollment = Enrollment::with('students')->findOrFail($validated['enrollment_id']);

        $attendance->update([
            'enrollment_id' => $enrollment->id,
            'status_validation' => 'pending',
            'validated_at' => null,
            'validated_by' => null,
        ]);

        $attendance->students()->sync(
            $enrollment->students
                ->mapWithKeys(fn ($student) => [$student->id => ['total_present' => 0]])
                ->all()
        );

        return back()->with('status', 'Enrollment diperbarui.');
    }

    public function validateAttendance(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:valid,revisi'],
        ]);

        $attendance->update([
            'status_validation' => $validated['status'],
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        if ($attendance->enrollment && $validated['status'] === 'valid') {
            $attendance->enrollment->update(['validation_status' => 1]);
        }

        return redirect()
            ->route('admin.presensi.show', $attendance)
            ->with('status', 'Presensi diperbarui.');
    }
}
