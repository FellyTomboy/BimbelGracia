<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = Student::with(['user', 'teachers'])
            ->latest()
            ->get();

        return view('admin.students.index', compact('students'));
    }

    public function inactive(): View
    {
        $students = Student::onlyTrashed()
            ->with(['user', 'teachers'])
            ->latest('deleted_at')
            ->get();

        return view('admin.students.inactive', compact('students'));
    }

    public function create(): View
    {
        return view('admin.students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_primary' => ['required', 'string', 'max:32'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $defaultPassword = config('bimbel.default_password', '12345678');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => UserRole::Murid,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'whatsapp_primary' => $validated['whatsapp_primary'],
            'whatsapp_secondary' => $validated['whatsapp_secondary'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil dibuat.');
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$student->user_id],
            'whatsapp_primary' => ['required', 'string', 'max:32'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $student->update([
            'name' => $validated['name'],
            'whatsapp_primary' => $validated['whatsapp_primary'],
            'whatsapp_secondary' => $validated['whatsapp_secondary'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        if ($student->user) {
            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid dihibernasi.');
    }

    public function restore(int $studentId): RedirectResponse
    {
        $student = Student::withTrashed()->findOrFail($studentId);
        $student->restore();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil dipulihkan.');
    }
}
