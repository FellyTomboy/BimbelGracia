<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AttendanceWindow;
use App\Models\Enrollment;
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

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'students',
        ])
            ->whereHas('enrollment', fn ($query) => $query->where('teacher_id', $teacher->id))
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

        $enrollments = Enrollment::with(['program', 'students'])
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return view('guru.presensi.create', compact('openWindow', 'enrollments'));
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
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'dates' => ['nullable', 'array'],
            'dates.*' => ['integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string'],
            'total_lessons' => ['required', 'integer', 'min:0'],
            'student_totals' => ['required', 'array'],
            'student_totals.*' => ['required', 'integer', 'min:0'],
        ]);

        $enrollment = Enrollment::with('students')
            ->where('id', $validated['enrollment_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $exists = MonthlyAttendance::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('month', $openWindow->month)
            ->where('year', $openWindow->year)
            ->exists();

        if ($exists) {
            $enrollment->update(['validation_status' => 2]);

            return back()
                ->withInput()
                ->withErrors(['enrollment_id' => 'Presensi untuk periode ini sudah ada.']);
        }

        $dates = collect($validated['dates'] ?? [])
            ->unique()
            ->values()
            ->all();

        if (count($dates) !== (int) $validated['total_lessons']) {
            return back()
                ->withInput()
                ->withErrors(['total_lessons' => 'Total pertemuan harus sama dengan jumlah tanggal.']);
        }

        $studentTotals = collect($validated['student_totals']);
        $studentIds = $enrollment->students->pluck('id')->map(fn ($id) => (string) $id);

        if ($studentTotals->keys()->diff($studentIds)->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['student_totals' => 'Murid tidak sesuai dengan enrollment yang dipilih.']);
        }

        if ($studentTotals->contains(fn ($total) => (int) $total > (int) $validated['total_lessons'])) {
            return back()
                ->withInput()
                ->withErrors(['student_totals' => 'Total hadir murid tidak boleh melebihi total pertemuan.']);
        }

        $attendance = MonthlyAttendance::create([
            'enrollment_id' => $enrollment->id,
            'month' => $openWindow->month,
            'year' => $openWindow->year,
            'dates' => $dates,
            'notes' => $validated['notes'] ?? null,
            'total_lessons' => $validated['total_lessons'],
            'status_validation' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        $attendance->students()->sync(
            $studentTotals
                ->mapWithKeys(fn ($total, $studentId) => [(int) $studentId => ['total_present' => (int) $total]])
                ->all()
        );

        $enrollment->update(['validation_status' => 1]);

        return redirect()
            ->route('guru.presensi.index')
            ->with('status', 'Presensi dikirim, menunggu validasi admin.');
    }

    public function edit(Request $request, MonthlyAttendance $attendance): View
    {
        $teacher = $this->resolveTeacher($request);

        abort_unless($attendance->enrollment?->teacher_id === $teacher->id, 403);

        if ($attendance->status_validation === 'valid') {
            abort(403);
        }

        $attendance->load(['enrollment.students', 'students']);

        return view('guru.presensi.edit', compact('attendance'));
    }

    public function update(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $teacher = $this->resolveTeacher($request);

        abort_unless($attendance->enrollment?->teacher_id === $teacher->id, 403);

        if ($attendance->status_validation === 'valid') {
            abort(403);
        }

        $validated = $request->validate([
            'dates' => ['nullable', 'array'],
            'dates.*' => ['integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string'],
            'total_lessons' => ['required', 'integer', 'min:0'],
            'student_totals' => ['required', 'array'],
            'student_totals.*' => ['required', 'integer', 'min:0'],
        ]);

        $dates = collect($validated['dates'] ?? [])
            ->unique()
            ->values()
            ->all();

        if (count($dates) !== (int) $validated['total_lessons']) {
            return back()
                ->withInput()
                ->withErrors(['total_lessons' => 'Total pertemuan harus sama dengan jumlah tanggal.']);
        }

        $attendance->load('enrollment.students');
        $studentTotals = collect($validated['student_totals']);
        $studentIds = $attendance->enrollment->students->pluck('id')->map(fn ($id) => (string) $id);

        if ($studentTotals->keys()->diff($studentIds)->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['student_totals' => 'Murid tidak sesuai dengan enrollment.']);
        }

        if ($studentTotals->contains(fn ($total) => (int) $total > (int) $validated['total_lessons'])) {
            return back()
                ->withInput()
                ->withErrors(['student_totals' => 'Total hadir murid tidak boleh melebihi total pertemuan.']);
        }

        $attendance->update([
            'dates' => $dates,
            'notes' => $validated['notes'] ?? null,
            'total_lessons' => $validated['total_lessons'],
            'status_validation' => 'pending',
        ]);

        $attendance->students()->sync(
            $studentTotals
                ->mapWithKeys(fn ($total, $studentId) => [(int) $studentId => ['total_present' => (int) $total]])
                ->all()
        );

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
