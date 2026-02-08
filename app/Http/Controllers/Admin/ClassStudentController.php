<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassStudentController extends Controller
{
    public function index(): View
    {
        $students = ClassStudent::query()
            ->orderBy('name')
            ->get();

        return view('admin.class-students.index', compact('students'));
    }

    public function inactive(): View
    {
        $students = ClassStudent::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('admin.class-students.inactive', compact('students'));
    }

    public function create(): View
    {
        return view('admin.class-students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_primary' => ['required', 'string', 'max:32'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:32'],
            'rate_per_meeting' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
            'notes' => ['nullable', 'string'],
        ]);

        ClassStudent::create($validated);

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama berhasil dibuat.');
    }

    public function edit(ClassStudent $classStudent): View
    {
        return view('admin.class-students.edit', compact('classStudent'));
    }

    public function update(Request $request, ClassStudent $classStudent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_primary' => ['required', 'string', 'max:32'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:32'],
            'rate_per_meeting' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
            'notes' => ['nullable', 'string'],
        ]);

        $classStudent->update($validated);

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama berhasil diperbarui.');
    }

    public function destroy(ClassStudent $classStudent): RedirectResponse
    {
        $classStudent->delete();

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama dihibernasi.');
    }

    public function restore(int $classStudentId): RedirectResponse
    {
        $classStudent = ClassStudent::withTrashed()->findOrFail($classStudentId);
        $classStudent->restore();

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama dipulihkan.');
    }
}
