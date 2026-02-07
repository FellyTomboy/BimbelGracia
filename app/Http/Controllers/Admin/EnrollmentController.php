<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(): View
    {
        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->withTrashed()
            ->latest()
            ->get();

        return view('admin.enrollments.index', compact('enrollments'));
    }

    public function create(): View
    {
        $programs = Program::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.enrollments.create', compact('programs', 'teachers', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'parent_rate' => ['required', 'integer', 'min:0'],
            'teacher_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $enrollment = Enrollment::create([
            'program_id' => $validated['program_id'],
            'teacher_id' => $validated['teacher_id'],
            'parent_rate' => $validated['parent_rate'],
            'teacher_rate' => $validated['teacher_rate'],
            'validation_status' => 0,
            'status' => $validated['status'],
        ]);

        $enrollment->students()->sync($validated['student_ids']);

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment berhasil dibuat.');
    }

    public function edit(Enrollment $enrollment): View
    {
        $enrollment->load('students');
        $programs = Program::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.enrollments.edit', compact('enrollment', 'programs', 'teachers', 'students'));
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'parent_rate' => ['required', 'integer', 'min:0'],
            'teacher_rate' => ['required', 'integer', 'min:0'],
            'validation_status' => ['required', 'integer', 'in:0,1,2'],
            'status' => ['required', 'in:active,hibernasi'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $enrollment->update([
            'program_id' => $validated['program_id'],
            'teacher_id' => $validated['teacher_id'],
            'parent_rate' => $validated['parent_rate'],
            'teacher_rate' => $validated['teacher_rate'],
            'validation_status' => $validated['validation_status'],
            'status' => $validated['status'],
        ]);

        $enrollment->students()->sync($validated['student_ids']);

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment berhasil diperbarui.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment dihibernasi.');
    }

    public function restore(int $enrollmentId): RedirectResponse
    {
        $enrollment = Enrollment::withTrashed()->findOrFail($enrollmentId);
        $enrollment->restore();

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment berhasil dipulihkan.');
    }
}
