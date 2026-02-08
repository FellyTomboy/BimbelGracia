<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = Teacher::with('user')
            ->latest()
            ->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function inactive(): View
    {
        $teachers = Teacher::onlyTrashed()
            ->with('user')
            ->latest('deleted_at')
            ->get();

        return view('admin.teachers.inactive', compact('teachers'));
    }

    public function create(): View
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_number' => ['required', 'string', 'max:32'],
            'major' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
            'class_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $defaultPassword = config('bimbel.default_password', '12345678');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => UserRole::Guru,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'whatsapp_number' => $validated['whatsapp_number'],
            'major' => $validated['major'] ?? null,
            'subjects' => $validated['subjects'] ?? null,
            'address' => $validated['address'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account' => $validated['bank_account'] ?? null,
            'bank_owner' => $validated['bank_owner'] ?? null,
            'class_rate' => $validated['class_rate'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil dibuat.');
    }

    public function edit(Teacher $teacher): View
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$teacher->user_id],
            'whatsapp_number' => ['required', 'string', 'max:32'],
            'major' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
            'class_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $teacher->update([
            'name' => $validated['name'],
            'whatsapp_number' => $validated['whatsapp_number'],
            'major' => $validated['major'] ?? null,
            'subjects' => $validated['subjects'] ?? null,
            'address' => $validated['address'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account' => $validated['bank_account'] ?? null,
            'bank_owner' => $validated['bank_owner'] ?? null,
            'class_rate' => $validated['class_rate'],
            'status' => $validated['status'],
        ]);

        if ($teacher->user) {
            $teacher->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru dihibernasi.');
    }

    public function restore(int $teacherId): RedirectResponse
    {
        $teacher = Teacher::withTrashed()->findOrFail($teacherId);
        $teacher->restore();

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil dipulihkan.');
    }
}
