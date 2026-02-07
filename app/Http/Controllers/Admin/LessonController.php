<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(): View
    {
        $lessons = Lesson::with(['teacher', 'student'])
            ->withTrashed()
            ->latest()
            ->get();

        return view('admin.lessons.index', compact('lessons'));
    }

    public function create(): View
    {
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.lessons.create', compact('teachers', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:lessons,code'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'student_id' => ['required', 'exists:students,id'],
            'parent_rate' => ['required', 'integer', 'min:0'],
            'teacher_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        Lesson::create([
            'code' => $validated['code'],
            'teacher_id' => $validated['teacher_id'],
            'student_id' => $validated['student_id'],
            'parent_rate' => $validated['parent_rate'],
            'teacher_rate' => $validated['teacher_rate'],
            'validation_status' => 0,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.lessons.index')
            ->with('status', 'Les privat berhasil dibuat.');
    }

    public function edit(Lesson $lesson): View
    {
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.lessons.edit', compact('lesson', 'teachers', 'students'));
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:lessons,code,'.$lesson->id],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'student_id' => ['required', 'exists:students,id'],
            'parent_rate' => ['required', 'integer', 'min:0'],
            'teacher_rate' => ['required', 'integer', 'min:0'],
            'validation_status' => ['required', 'integer', 'in:0,1,2'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $lesson->update($validated);

        return redirect()
            ->route('admin.lessons.index')
            ->with('status', 'Les privat berhasil diperbarui.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return redirect()
            ->route('admin.lessons.index')
            ->with('status', 'Les privat dihibernasi.');
    }

    public function restore(int $lessonId): RedirectResponse
    {
        $lesson = Lesson::withTrashed()->findOrFail($lessonId);
        $lesson->restore();

        return redirect()
            ->route('admin.lessons.index')
            ->with('status', 'Les privat berhasil dipulihkan.');
    }
}
