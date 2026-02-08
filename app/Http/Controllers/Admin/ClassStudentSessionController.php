<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Models\ClassStudentSession;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassStudentSessionController extends Controller
{
    public function index(): View
    {
        $sessions = ClassStudentSession::with('student')
            ->latest('session_date')
            ->get();

        return view('admin.class-student-sessions.index', compact('sessions'));
    }

    public function inactive(): View
    {
        $sessions = ClassStudentSession::onlyTrashed()
            ->with('student')
            ->latest('deleted_at')
            ->get();

        return view('admin.class-student-sessions.inactive', compact('sessions'));
    }

    public function calendar(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);
        $studentId = $request->input('class_student_id');

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $sessions = ClassStudentSession::with('student')
            ->whereBetween('session_date', [$start, $end])
            ->when($studentId, fn ($query) => $query->where('class_student_id', $studentId))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $sessionsByDate = $sessions->groupBy(function (ClassStudentSession $session) {
            return $session->session_date->format('Y-m-d');
        });

        $students = ClassStudent::orderBy('name')->get();

        return view('admin.class-student-sessions.calendar', [
            'month' => $month,
            'year' => $year,
            'classStudentId' => $studentId,
            'start' => $start,
            'daysInMonth' => $start->daysInMonth,
            'firstDayOfWeek' => $start->dayOfWeekIso,
            'sessionsByDate' => $sessionsByDate,
            'students' => $students,
        ]);
    }

    public function create(): View
    {
        $students = ClassStudent::orderBy('name')->get();

        return view('admin.class-student-sessions.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_student_id' => ['required', 'exists:class_students,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        ClassStudentSession::create($validated);

        return redirect()
            ->route('admin.class-student-sessions.index')
            ->with('status', 'Jadwal murid kelas berhasil dibuat.');
    }

    public function edit(ClassStudentSession $classStudentSession): View
    {
        $students = ClassStudent::orderBy('name')->get();

        return view('admin.class-student-sessions.edit', compact('classStudentSession', 'students'));
    }

    public function update(Request $request, ClassStudentSession $classStudentSession): RedirectResponse
    {
        $validated = $request->validate([
            'class_student_id' => ['required', 'exists:class_students,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $classStudentSession->update($validated);

        return redirect()
            ->route('admin.class-student-sessions.index')
            ->with('status', 'Jadwal murid kelas berhasil diperbarui.');
    }

    public function destroy(ClassStudentSession $classStudentSession): RedirectResponse
    {
        $classStudentSession->delete();

        return redirect()
            ->route('admin.class-student-sessions.index')
            ->with('status', 'Jadwal murid kelas dihibernasi.');
    }

    public function restore(int $classStudentSessionId): RedirectResponse
    {
        $classStudentSession = ClassStudentSession::withTrashed()->findOrFail($classStudentSessionId);
        $classStudentSession->restore();

        return redirect()
            ->route('admin.class-student-sessions.index')
            ->with('status', 'Jadwal murid kelas dipulihkan.');
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
