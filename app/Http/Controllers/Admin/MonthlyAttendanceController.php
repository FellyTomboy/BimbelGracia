<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Student;
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
        $isClassPlaceholder = $this->hasClassPlaceholderStudent($attendance->students);

        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->when($isClassPlaceholder, function ($query) {
                $query->whereHas('program', fn ($sub) => $sub->where('type', 'kelas'));
            })
            ->orderBy('id')
            ->get();

        return view('admin.presensi.show', compact('attendance', 'enrollments', 'isClassPlaceholder'));
    }

    public function updateEnrollment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
        ]);

        $attendance->load('students');
        $enrollment = Enrollment::with(['students', 'program'])->findOrFail($validated['enrollment_id']);

        if ($this->hasClassPlaceholderStudent($attendance->students) && $enrollment->program?->type !== 'kelas') {
            return back()->withErrors([
                'enrollment_id' => 'Presensi murid kelas bersama harus memakai program bertipe kelas.',
            ]);
        }

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

    private function hasClassPlaceholderStudent($students): bool
    {
        $placeholder = (string) config('bimbel.class_student_placeholder', 'Murid Kelas Bersama');
        $placeholder = trim($placeholder);
        if ($placeholder === '') {
            return false;
        }

        $needle = strtolower($placeholder);

        return $students->contains(function (Student $student) use ($needle): bool {
            return strtolower($student->name) === $needle;
        });
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
