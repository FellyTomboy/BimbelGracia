<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassSessionController extends Controller
{
    public function index(): View
    {
        $sessions = ClassSession::with(['classGroup', 'teacher'])
            ->withCount('students')
            ->withTrashed()
            ->latest('session_date')
            ->get();

        return view('admin.class-sessions.index', compact('sessions'));
    }

    public function create(Request $request): View
    {
        $groups = ClassGroup::with('teacher')->orderBy('name')->get();
        $selectedGroup = null;
        $students = collect();

        if ($request->filled('class_group_id')) {
            $selectedGroup = ClassGroup::with('students')
                ->find($request->input('class_group_id'));
            $students = $selectedGroup?->students ?? collect();
        }

        return view('admin.class-sessions.create', compact('groups', 'selectedGroup', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_group_id' => ['required', 'exists:class_groups,id'],
            'session_date' => ['required', 'date'],
            'session_time' => ['nullable', 'date_format:H:i'],
            'subject' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $group = ClassGroup::with('teacher')->findOrFail($validated['class_group_id']);

        $session = ClassSession::create([
            'class_group_id' => $group->id,
            'teacher_id' => $group->teacher_id,
            'subject' => $validated['subject'] ?? $group->subject,
            'session_date' => $validated['session_date'],
            'session_time' => $validated['session_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $studentIds = $validated['student_ids'] ?? [];
        $syncData = collect($studentIds)->mapWithKeys(function (int $studentId) {
            return [$studentId => ['is_present' => true]];
        })->all();

        $session->students()->sync($syncData);

        return redirect()
            ->route('admin.class-sessions.index')
            ->with('status', 'Jadwal kelas berhasil dibuat.');
    }

    public function show(ClassSession $classSession): View
    {
        $classSession->load(['classGroup', 'teacher', 'students']);

        return view('admin.class-sessions.show', compact('classSession'));
    }

    public function edit(ClassSession $classSession): View
    {
        $classSession->load(['classGroup', 'students']);
        $groups = ClassGroup::with('teacher')->orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.class-sessions.edit', compact('classSession', 'groups', 'students'));
    }

    public function update(Request $request, ClassSession $classSession): RedirectResponse
    {
        $validated = $request->validate([
            'class_group_id' => ['required', 'exists:class_groups,id'],
            'session_date' => ['required', 'date'],
            'session_time' => ['nullable', 'date_format:H:i'],
            'subject' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $group = ClassGroup::findOrFail($validated['class_group_id']);

        $classSession->update([
            'class_group_id' => $group->id,
            'teacher_id' => $group->teacher_id,
            'subject' => $validated['subject'] ?? $group->subject,
            'session_date' => $validated['session_date'],
            'session_time' => $validated['session_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $existing = $classSession->students->pluck('pivot.is_present', 'id')->toArray();
        $studentIds = $validated['student_ids'] ?? [];
        $syncData = collect($studentIds)->mapWithKeys(function (int $studentId) use ($existing) {
            return [$studentId => ['is_present' => $existing[$studentId] ?? true]];
        })->all();

        $classSession->students()->sync($syncData);

        return redirect()
            ->route('admin.class-sessions.index')
            ->with('status', 'Jadwal kelas diperbarui.');
    }

    public function updateAttendance(Request $request, ClassSession $classSession): RedirectResponse
    {
        $validated = $request->validate([
            'present_ids' => ['nullable', 'array'],
            'present_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $presentIds = collect($validated['present_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

        $syncData = $classSession->students
            ->mapWithKeys(function (Student $student) use ($presentIds) {
                return [$student->id => ['is_present' => in_array($student->id, $presentIds, true)]];
            })
            ->all();

        $classSession->students()->sync($syncData);

        return redirect()
            ->route('admin.class-sessions.show', $classSession)
            ->with('status', 'Kehadiran diperbarui.');
    }

    public function destroy(ClassSession $classSession): RedirectResponse
    {
        $classSession->delete();

        return redirect()
            ->route('admin.class-sessions.index')
            ->with('status', 'Jadwal kelas dihibernasi.');
    }

    public function restore(int $classSessionId): RedirectResponse
    {
        $classSession = ClassSession::withTrashed()->findOrFail($classSessionId);
        $classSession->restore();

        return redirect()
            ->route('admin.class-sessions.index')
            ->with('status', 'Jadwal kelas dipulihkan.');
    }
}
