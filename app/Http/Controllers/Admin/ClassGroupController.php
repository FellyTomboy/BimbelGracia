<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\Teacher;
use App\Traits\SearchAndSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassGroupController extends Controller
{
    use SearchAndSort;

    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $groups = ClassGroup::with(['teacher', 'students'])
            ->withTrashed();

        $groups = $this->applySearch($groups, $params['search'], [
            'class_groups.name',
            'class_groups.subject',
            'teacher.name',
        ]);

        $groups = $this->applySort($groups, $params['sort'], $params['direction'], [
            'class_groups.name', 'class_groups.subject', 'class_groups.created_at',
        ]);

        $groups = $groups->paginate(20)->withQueryString();

        return view('admin.class-groups.index', compact('groups'));
    }

    public function create(): View
    {
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.class-groups.create', compact('teachers', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'notes' => ['nullable', 'string'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $group = ClassGroup::create([
            'name' => $validated['name'],
            'subject' => $validated['subject'] ?? null,
            'teacher_id' => $validated['teacher_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $group->students()->sync($validated['student_ids'] ?? []);

        return redirect()
            ->route('admin.class-groups.index')
            ->with('status', 'Kelas bersama berhasil dibuat.');
    }

    public function edit(ClassGroup $classGroup): View
    {
        $classGroup->load('students');
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.class-groups.edit', compact('classGroup', 'teachers', 'students'));
    }

    public function update(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'notes' => ['nullable', 'string'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $classGroup->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'] ?? null,
            'teacher_id' => $validated['teacher_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $classGroup->students()->sync($validated['student_ids'] ?? []);

        return redirect()
            ->route('admin.class-groups.index')
            ->with('status', 'Kelas bersama diperbarui.');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $classGroup->delete();

        return redirect()
            ->route('admin.class-groups.index')
            ->with('status', 'Kelas bersama dihibernasi.');
    }

    public function restore(int $classGroupId): RedirectResponse
    {
        $classGroup = ClassGroup::withTrashed()->findOrFail($classGroupId);
        $classGroup->restore();

        return redirect()
            ->route('admin.class-groups.index')
            ->with('status', 'Kelas bersama dipulihkan.');
    }
}
