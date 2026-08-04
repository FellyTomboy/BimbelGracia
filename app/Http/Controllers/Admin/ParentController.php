<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(): View
    {
        $parents = ParentModel::with(['user', 'students'])
            ->orderBy('id')
            ->paginate(20);

        return view('admin.parents.index', compact('parents'));
    }

    public function create(): View
    {
        return view('admin.parents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $phone = $this->cleanPhone($validated['phone']);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'role' => UserRole::Parent,
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        ParentModel::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil ditambahkan.');
    }

    public function edit(ParentModel $parent): View
    {
        $parent->load(['user', 'students']);

        return view('admin.parents.edit', compact('parent'));
    }

    public function update(Request $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $parent->user_id],
        ]);

        $phone = $this->cleanPhone($validated['phone']);

        $parent->user->update([
            'name' => $validated['name'],
            'phone' => $phone,
        ]);

        $parent->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil diperbarui.');
    }

    public function destroy(ParentModel $parent): RedirectResponse
    {
        if ($parent->students()->count() > 0) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus parent yang masih memiliki murid. Hapus murid terlebih dahulu.']);
        }

        $parent->user->delete();
        $parent->delete();

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil dihapus.');
    }

    public function addStudent(Request $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        Student::create([
            'parent_id' => $parent->id,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.parents.edit', $parent->id)
            ->with('status', 'Murid berhasil ditambahkan.');
    }

    public function removeStudent(ParentModel $parent, Student $student): RedirectResponse
    {
        if ($student->parent_id !== $parent->id) {
            abort(404);
        }

        $student->delete();

        return redirect()->route('admin.parents.edit', $parent->id)
            ->with('status', 'Murid berhasil dihapus.');
    }

    public function changePassword(Request $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $parent->user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('admin.parents.index')
            ->with('status', 'Password parent berhasil diubah.');
    }

    private function cleanPhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')', '+'], '', $phone);

        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (!str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}