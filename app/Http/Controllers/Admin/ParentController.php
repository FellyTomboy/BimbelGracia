<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Helpers\StudentGrade;
use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            ->with([
                'user' => fn($q) => $q->withTrashed(),
                'students' => fn($q) => $q->withTrashed()->orderBy('full_name'),
            ])
            ->withCount(['students'])
            ->latest('deleted_at')
            ->paginate(20);

        return view('admin.parents.inactive', compact('parents'));
    }

    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.parents.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^08[0-9]{8,12}$/', 'unique:users,phone'],
            'address' => ['nullable', 'string', 'max:500'],
            'students' => ['nullable', 'array'],
            'students.*.nickname' => ['nullable', 'string', 'max:255'],
            'students.*.full_name' => ['nullable', 'string', 'max:255'],
            'students.*.sekolah' => ['nullable', 'string', 'max:255'],
            'students.*.kelas' => ['nullable', 'string', Rule::in(StudentGrade::LEVELS)],
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
                    $sekolah = trim((string) ($studentData['sekolah'] ?? '')) ?: null;
                    $kelas = $studentData['kelas'] ?? null;
                    Student::create([
                        'parent_id' => $parent->id,
                        'nickname' => $nickname,
                        'full_name' => $fullName,
                        'sekolah' => $sekolah,
                        'kelas' => $kelas,
                    ]);
                    $studentCount++;
                }
            }
        }

        $message = 'Parent berhasil ditambahkan.';
        if ($studentCount > 0) {
            $message .= " {$studentCount} murid berhasil ditambahkan.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('admin.parents.index')
            ->with('status', $message);
    }

    public function createForm(Request $request): JsonResponse
    {
        $html = view('admin.parents._form', [
            'parent' => null,
        ])->render();

        return response()->json(['html' => $html, 'title' => 'Tambah Orang Tua']);
    }

    public function edit(ParentModel $parent): View
    {
        $parent->load(['user', 'students']);

        return view('admin.parents.edit', compact('parent'));
    }

    public function editForm(Request $request, ParentModel $parent): JsonResponse
    {
        $parent->load(['user', 'students']);
        $html = view('admin.parents._form', [
            'parent' => $parent,
        ])->render();

        return response()->json(['html' => $html, 'title' => 'Edit Orang Tua']);
    }

    public function studentsJson(Request $request, ParentModel $parent): JsonResponse
    {
        $parent->load(['user', 'students']);

        $students = $parent->students->map(fn($s) => [
            'id' => $s->id,
            'nickname' => $s->nickname,
            'full_name' => $s->full_name,
            'sekolah' => $s->sekolah,
            'kelas' => $s->kelas,
            'status' => $s->status,
        ]);

        return response()->json(['students' => $students]);
    }

    public function update(Request $request, ParentModel $parent): JsonResponse|RedirectResponse
    {
        $userId = $parent->user_id;
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^08[0-9]{8,12}$/', $userId ? Rule::unique('users', 'phone')->ignore($userId) : 'unique:users,phone'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = $this->cleanPhone($validated['phone']);
        $parentName = trim((string) ($validated['name'] ?? '')) ?: null;

        if ($parent->user) {
            $parent->user->update([
                'name' => $parentName ?: 'Orang Tua',
                'phone' => $phone,
            ]);
        }

        $parent->update([
            'name' => $parentName,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Parent berhasil diperbarui.']);
        }
        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil diperbarui.');
    }

    public function destroy(ParentModel $parent): JsonResponse|RedirectResponse
    {
        if ($parent->students()->count() > 0) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus parent yang masih memiliki murid. Hapus murid terlebih dahulu.'], 422);
            }
            return back()->withErrors(['error' => 'Tidak dapat menghapus parent yang masih memiliki murid. Hapus murid terlebih dahulu.']);
        }

        $parent->user->delete();
        $parent->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Parent berhasil dihapus.']);
        }
        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent berhasil dihapus.');
    }

    public function hibernate(ParentModel $parent): JsonResponse|RedirectResponse
    {
        // Cascade: hibernasi parent + semua murid di bawahnya
        DB::transaction(function () use ($parent): void {
            foreach ($parent->students as $student) {
                $student->update(['status' => 'hibernasi']);
                $student->delete();
            }

            if ($parent->user) {
                $parent->user->delete();
            }
            $parent->delete();
        });

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Parent beserta semua murid berhasil dihibernasi.']);
        }
        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent beserta semua murid berhasil dihibernasi.');
    }

    public function bulkRestore(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:parents,id'],
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $parent = ParentModel::withTrashed()
                ->with(['user' => fn($q) => $q->withTrashed()])
                ->find($id);
            if (! $parent) {
                continue;
            }

            DB::transaction(function () use ($parent): void {
                $parent->restore();
                if ($parent->user) {
                    $parent->user->restore();
                }
                foreach (Student::withTrashed()->where('parent_id', $parent->id)->get() as $student) {
                    $student->restore();
                    $student->status = 'active';
                    $student->save();
                }
            });
            $count++;
        }

        $message = "{$count} parent beserta murid-muridnya berhasil dipulihkan.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('admin.parents.index')
            ->with('status', $message);
    }

    public function restore(Request $request): JsonResponse|RedirectResponse
    {
        $parentId = $request->route('parent');
        $parent = ParentModel::withTrashed()
            ->with(['user' => fn($q) => $q->withTrashed()])
            ->findOrFail($parentId);

        DB::transaction(function () use ($parent): void {
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
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Parent beserta semua murid berhasil dipulihkan.']);
        }
        return redirect()->route('admin.parents.index')
            ->with('status', 'Parent beserta semua murid berhasil dipulihkan.');
    }

    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
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

                if ($parent->user) {
                    $parent->user->delete();
                }
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()
            ->route('admin.parents.index')
            ->with('status', $message);
    }

    public function addStudent(Request $request, ParentModel $parent): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'sekolah' => ['nullable', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', Rule::in(StudentGrade::LEVELS)],
        ]);

        $nickname = trim($validated['nickname']);
        $existing = Student::where('parent_id', $parent->id)
            ->whereRaw('LOWER(TRIM(nickname)) = ?', [strtolower($nickname)])
            ->first();
        if ($existing) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => ['nickname' => ["Murid \"{$nickname}\" sudah terdaftar di bawah parent ini."]]], 422);
            }
            return back()->withErrors(['nickname' => "Murid \"{$nickname}\" sudah terdaftar di bawah parent ini."])->withInput();
        }

        $student = Student::create([
            'parent_id' => $parent->id,
            'nickname' => $nickname,
            'full_name' => $validated['full_name'] ?? null,
            'sekolah' => $validated['sekolah'] ?? null,
            'kelas' => $validated['kelas'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Murid {$student->display_name} berhasil ditambahkan."]);
        }
        return redirect()
            ->route('admin.parents.edit', $parent)
            ->with('status', "Murid {$student->display_name} berhasil ditambahkan.");
    }

    public function updateStudent(Request $request, ParentModel $parent, Student $student): JsonResponse|RedirectResponse
    {
        if ($student->parent_id !== $parent->id) {
            abort(404);
        }

        $validated = $request->validate([
            'sekolah' => ['nullable', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', Rule::in(StudentGrade::LEVELS)],
        ]);

        $student->update([
            'sekolah' => trim((string) ($validated['sekolah'] ?? '')) ?: null,
            'kelas' => $validated['kelas'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Data murid {$student->display_name} berhasil diperbarui."]);
        }
        return redirect()
            ->route('admin.parents.edit', $parent)
            ->with('status', "Data murid {$student->display_name} berhasil diperbarui.");
    }

    public function removeStudent(ParentModel $parent, Student $student): JsonResponse|RedirectResponse
    {
        if ($student->parent_id !== $parent->id) {
            abort(404);
        }

        DB::transaction(function () use ($student): void {
            $student->update(['status' => 'hibernasi']);
            $student->delete();
        });

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Murid {$student->display_name} berhasil dihibernasi."]);
        }
        return redirect()
            ->route('admin.parents.edit', $parent)
            ->with('status', "Murid {$student->display_name} berhasil dihibernasi.");
    }

    public function changePassword(Request $request, ParentModel $parent): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! $parent->user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 422);
            }
            return back()->withErrors(['error' => 'User tidak ditemukan.']);
        }

        $parent->user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password parent berhasil diubah.']);
        }
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

    public function forceDestroy(Request $request, int $parentId): JsonResponse|RedirectResponse
    {
        $parent = ParentModel::onlyTrashed()->findOrFail($parentId);

        $validated = $request->validate([
            'acknowledge_cascade' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($parent) {
            // Cascade: force-delete students (which cascade to student_teacher, enrollment_student)
            $parent->students()->withTrashed()->get()->each->forceDelete();
            // Force-delete user if exists (nullOnDelete FK handles this too)
            if ($parent->user) {
                $parent->user->forceDelete();
            }
            $parent->forceDelete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Parent dan data terkait dihapus permanen.']);
        }

        return redirect()
            ->route('admin.parents.inactive')
            ->with('status', 'Parent dan data terkait dihapus permanen.');
    }

    public function bulkForceDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'acknowledge_cascade' => ['required', 'accepted'],
        ]);

        $count = DB::transaction(function () use ($validated) {
            $parents = ParentModel::onlyTrashed()->whereIn('id', $validated['ids'])->get();
            $deleted = 0;
            foreach ($parents as $parent) {
                $parent->students()->withTrashed()->get()->each->forceDelete();
                if ($parent->user) {
                    $parent->user->forceDelete();
                }
                $parent->forceDelete();
                $deleted++;
            }
            return $deleted;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "{$count} parent dihapus permanen."]);
        }

        return redirect()
            ->route('admin.parents.inactive')
            ->with('status', "{$count} parent dihapus permanen.");
    }
}