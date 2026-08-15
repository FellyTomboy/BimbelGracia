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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(Request $request): View
    {
        $query = ParentModel::with(['user', 'students']);

        // Searching
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('phone', 'like', "%{$search}%"))
                    ->orWhereHas('students', fn($sq) => $sq->where('nickname', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%"));
            });
        }

        // Sorting
        $sortBy = $request->input('sort', 'id');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['id', 'name', 'phone'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'phone') {
            $query->join('users', 'parents.user_id', '=', 'users.id')
                ->orderBy('users.phone', $sortDir)
                ->select('parents.*');
        } else {
            $query->orderBy("parents.{$sortBy}", $sortDir);
        }

        $parents = $query->paginate(20)->appends($request->query());

        return view('admin.parents.index', compact('parents', 'sortBy', 'sortDir'));
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
            'phone' => ['required', 'string', 'max:20', 'regex:/^08[0-9]{8,12}$/', 'unique:users,phone'],
            'address' => ['nullable', 'string', 'max:500'],
            'students' => ['nullable', 'array'],
            'students.*.nickname' => ['nullable', 'string', 'max:255'],
            'students.*.full_name' => ['nullable', 'string', 'max:255'],
        ]);

        $defaultPassword = config('bimbel.default_password', 'password');
        $phone = $this->cleanPhone($validated['phone']);
        $parentName = trim((string) ($validated['name'] ?? '')) ?: null;

        $user = User::create([
            'name' => $parentName ?: 'Orang Tua',
            'phone' => $phone,
            'role' => UserRole::Parent,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        $parent = ParentModel::create([
            'user_id' => $user->id,
            'name' => $parentName,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
        ]);

        $studentCount = 0;
        if (!empty($validated['students'])) {
            foreach ($validated['students'] as $studentData) {
                $nickname = trim((string) ($studentData['nickname'] ?? $studentData['name'] ?? ''));
                if ($nickname !== '') {
                    $existing = Student::where('parent_id', $parent->id)
                        ->whereRaw('LOWER(TRIM(nickname)) = ?', [strtolower($nickname)])
                        ->first();
                    if ($existing) {
                        return back()->withErrors([
                            'students' => "Murid \"{$nickname}\" sudah terdaftar di bawah parent ini.",
                        ])->withInput();
                    }

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
            'phone' => ['required', 'string', 'max:20', 'regex:/^08[0-9]{8,12}$/', 'unique:users,phone,' . $parent->user_id],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = $this->cleanPhone($validated['phone']);
        $parentName = trim((string) ($validated['name'] ?? '')) ?: null;

        $parent->user->update([
            'name' => $parentName ?: 'Orang Tua',
            'phone' => $phone,
        ]);

        $parent->update([
            'name' => $parentName,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
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
        // Cascade: hibernasi parent + semua murid di bawahnya
        DB::transaction(function () use ($parent): void {
            foreach ($parent->students as $student) {
                $student->update(['status' => 'hibernasi']);
                $student->delete();
            }

            $parent->user->delete();
            $parent->delete();
        });

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent beserta semua murid berhasil dihibernasi.');
    }

    public function restore(int $parentId): RedirectResponse
    {
        $parent = ParentModel::withTrashed()->findOrFail($parentId);

        $parent->restore();

        if ($parent->user) {
            $parent->user->restore();
        }

        // Cascade: restore semua murid
        foreach (Student::withTrashed()->where('parent_id', $parent->id)->get() as $student) {
            $student->restore();
            $student->status = 'active';
            $student->save();
        }

        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent beserta semua murid berhasil dipulihkan.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:parents,id'],
        ]);

        $count = 0;
        $skipped = 0;

        foreach ($validated['ids'] as $id) {
            $parent = ParentModel::find($id);
            if (! $parent) {
                continue;
            }

            // Count active enrollments before hibernating
            $activeEnrollments = $parent->students->flatMap->enrollments->where('status', 'active')->count();

            DB::transaction(function () use ($parent): void {
                foreach ($parent->students as $student) {
                    $student->update(['status' => 'hibernasi']);
                    $student->delete();
                }

                $parent->user->delete();
                $parent->delete();
            });

            if ($activeEnrollments > 0) {
                $skipped++;
            }
            $count++;
        }

        $message = "{$count} parent beserta murid-muridnya berhasil dihibernasi.";
        if ($skipped > 0) {
            $message .= " {$skipped} di antaranya memiliki enrollment aktif yang tetap dipertahankan.";
        }

        return redirect()
            ->route('admin.parents.index')
            ->with('status', $message);
    }

    public function addStudent(Request $request, ParentModel $parent): RedirectResponse
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
        ]);

        $nickname = trim($validated['nickname']);
        $existing = Student::where('parent_id', $parent->id)
            ->whereRaw('LOWER(TRIM(nickname)) = ?', [strtolower($nickname)])
            ->first();
        if ($existing) {
            return back()->withErrors(['nickname' => "Murid \"{$nickname}\" sudah terdaftar di bawah parent ini."])->withInput();
        }

        $student = Student::create([
            'parent_id' => $parent->id,
            'nickname' => $nickname,
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

        DB::transaction(function () use ($student): void {
            $student->update(['status' => 'hibernasi']);
            $student->delete();
        });

        return redirect()
            ->route('admin.parents.edit', $parent)
            ->with('status', "Murid {$student->display_name} berhasil dihibernasi.");
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

        // Keep as 08XXXXXXXXX format in database
        // Only convert to 628 when generating wa.me links (via WhatsappHelper)
        if (strlen($phone) > 13) {
            $phone = substr($phone, -13);
        }

        // Ensure starts with 08
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (! str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}