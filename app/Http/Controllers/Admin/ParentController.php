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

    public function inactive(): View
    {
        $parents = ParentModel::onlyTrashed()
            ->with(['user', 'students'])
            ->latest('deleted_at')
            ->paginate(20);

        return view('admin.parents.inactive', compact('parents'));
    }

    public function create(): View
    {
        return view('admin.parents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'students' => ['nullable', 'array'],
            'students.*.nickname' => ['nullable', 'string', 'max:255'],
            'students.*.full_name' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = $this->cleanPhone($validated['phone']);
        $parentName = trim((string) ($validated['name'] ?? '')) ?: null;

        $user = User::create([
            'name' => $parentName ?: 'Orang Tua',
            'phone' => $phone,
            'role' => UserRole::Parent,
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        $parent = ParentModel::create([
            'user_id' => $user->id,
            'name' => $parentName,
        ]);

        $studentCount = 0;
        if (!empty($validated['students'])) {
            foreach ($validated['students'] as $studentData) {
                $nickname = trim((string) ($studentData['nickname'] ?? $studentData['name'] ?? ''));
                if ($nickname !== '') {
                    $fullName = trim((string) ($studentData['full_name'] ?? '')) ?: null;
                    Student::create([
                        'parent_id' => $parent->id,
                        'nickname' => $nickname,
                        'full_name' => $fullName,
                    ]);
                    $studentCount++;
                }
            }
        }

        $message = 'Parent berhasil ditambahkan.';
        if ($studentCount > 0) {
            $message .= " {$studentCount} murid berhasil ditambahkan.";
        }

        return redirect()->route('admin.parents.index')
            ->with('status', $message);
    }

    public function edit(ParentModel $parent): View
    {
        $parent->load(['user', 'students']);

        return view('admin.parents.edit', compact('parent'));
    }

    public function update(Request $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $parent->user_id],
        ]);

        $phone = $this->cleanPhone($validated['phone']);
        $parentName = trim((string) ($validated['name'] ?? '')) ?: null;

        $parent->user->update([
            'name' => $parentName ?: 'Orang Tua',
            'phone' => $phone,
        ]);

        $parent->update([
            'name' => $parentName,
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

    public function hibernate(ParentModel $parent): RedirectResponse
    {
        if ($parent->students()->count() > 0) {
            return back()->withErrors(['error' => 'Tidak dapat menghibernasi parent yang masih memiliki murid. Hapus/hibernasi murid terlebih dahulu.']);
        }

        $parent->user->delete();
        $parent->delete();

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil dihibernasi.');
    }

    public function restore(int $parentId): RedirectResponse
    {
        $parent = ParentModel::withTrashed()->findOrFail($parentId);

        $parent->restore();

        if ($parent->user) {
            $parent->user->restore();
        }

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil dipulihkan.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:parents,id'],
        ]);

        $count = 0;

        foreach ($validated['ids'] as $id) {
            $parent = ParentModel::find($id);
            if ($parent && $parent->students()->count() === 0) {
                $parent->user->delete();
                $parent->delete();
                $count++;
            }
        }

        return redirect()
            ->route('admin.parents.index')
            ->with('status', "{$count} parent berhasil dihibernasi.");
    }

    public function addStudent(Request $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::create([
            'parent_id' => $parent->id,
            'nickname' => $validated['nickname'],
            'full_name' => $validated['full_name'] ?? null,
        ]);

        return redirect()
            ->route('admin.parents.edit', $parent)
            ->with('status', "Murid {$student->display_name} berhasil ditambahkan.");
    }

    public function removeStudent(ParentModel $parent, Student $student): RedirectResponse
    {
        if ($student->parent_id !== $parent->id) {
            abort(404);
        }

        $student->delete();

        return redirect()
            ->route('admin.parents.edit', $parent)
            ->with('status', "Murid {$student->display_name} berhasil dihapus.");
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

        return redirect()->route('admin.parents.edit', $parent)
            ->with('status', 'Password parent berhasil diubah.');
    }

    private function cleanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) > 12) {
            $phone = substr($phone, -12);
        }

        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}