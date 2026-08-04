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

class ClassAttendanceController extends Controller
{
    /**
     * List class attendances that have NO students filled yet.
     */
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->whereHas('enrollment.program', fn ($q) => $q->where('type', 'kelompok'))
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('lesson_date')
            ->get();

        return view('admin.class-attendance.index', [
            'month' => $month,
            'year' => $year,
            'attendances' => $attendances,
        ]);
    }

    /**
     * Show form to fill students for a specific class attendance.
     */
    public function edit(Request $request, MonthlyAttendance $attendance): View
    {
        $enrollment = $attendance->enrollment;
        abort_unless($enrollment?->program?->type === 'kelompok', 404);

        $allStudents = Student::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $attendance->load('students');

        return view('admin.class-attendance.edit', [
            'attendance' => $attendance,
            'enrollment' => $enrollment,
            'allStudents' => $allStudents,
            'selectedStudentIds' => $attendance->students->pluck('id')->toArray(),
        ]);
    }

    /**
     * Save the selected students for a class attendance.
     */
    public function update(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $enrollment = $attendance->enrollment;
        abort_unless($enrollment?->program?->type === 'kelompok', 404);

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $studentIds = $validated['student_ids'] ?? [];
        $presentCount = count($studentIds);

        if ($presentCount > 0) {
            // Calculate parent rate based on pricing tiers or fallback
            $parentRate = $enrollment->getParentRateForCount($presentCount);

            $attendance->students()->sync(
                collect($studentIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
            );

            // Update parent_rate snapshot
            $attendance->update(['parent_rate' => $parentRate]);
        } else {
            $attendance->students()->detach();
        }

        return redirect()
            ->route('admin.class-attendance.index')
            ->with('status', 'Daftar murid untuk sesi kelas berhasil diperbarui.');
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