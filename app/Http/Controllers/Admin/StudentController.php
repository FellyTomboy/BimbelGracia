<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use App\Services\MonthlySnapshotSyncService;
use App\Traits\SearchAndSort;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    use SearchAndSort;

    public function __construct(private MonthlySnapshotSyncService $snapshotSyncService)
    {
    }
    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $students = Student::with(['parent.user', 'teachers']);

        $students = $this->applySearch($students, $params['search'], [
            'students.full_name',
            'students.nickname',
            'students.status',
        ]);

        $students = $this->applySort($students, $params['sort'], $params['direction'], [
            'students.full_name', 'students.nickname', 'students.status', 'students.created_at',
        ]);

        $students = $students->paginate(20)->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function inactive(): View
    {
        $students = Student::withTrashed()
            ->where('status', 'hibernasi')
            ->with(['parent.user', 'teachers'])
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
            'nickname' => ['required', 'string'],
            'full_name' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $nickname = trim((string) ($validated['nickname'] ?? ''));
        $fullName = trim((string) ($validated['full_name'] ?? ''));
        $parentName = trim((string) ($validated['parent_name'] ?? '')) ?: null;

        if ($nickname === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nickname' => ['Nickname murid wajib diisi.'],
            ]);
        }

        $defaultPassword = config('bimbel.default_password', '12345678');
        $phone = $validated['whatsapp'];

        DB::transaction(function () use ($phone, $defaultPassword, $nickname, $fullName, $parentName, $validated) {
            $userName = $parentName ?: 'Orang Tua';

            $user = User::create([
                'name' => $userName,
                'phone' => $phone,
                'role' => UserRole::Parent,
                'password' => Hash::make($defaultPassword),
                'must_change_password' => true,
            ]);

            $parent = ParentModel::create([
                'user_id' => $user->id,
                'name' => $parentName,
            ]);

            Student::create([
                'parent_id' => $parent->id,
                'nickname' => $nickname,
                'full_name' => $fullName !== '' ? $fullName : null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'],
            ]);
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil dibuat.');
    }

    public function edit(Student $student): View
    {
        $student->load('parent.user');
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string'],
            'full_name' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:32', 'unique:users,phone,'.($student->parent?->user_id ?? 'NULL')],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $nickname = trim((string) ($validated['nickname'] ?? ''));
        $fullName = trim((string) ($validated['full_name'] ?? ''));
        $parentName = trim((string) ($validated['parent_name'] ?? '')) ?: null;

        if ($nickname === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nickname' => ['Nickname murid wajib diisi.'],
            ]);
        }

        $phone = $validated['whatsapp'];

        DB::transaction(function () use ($student, $nickname, $fullName, $parentName, $validated, $phone) {
            $student->update([
                'nickname' => $nickname,
                'full_name' => $fullName !== '' ? $fullName : null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'],
            ]);

            if ($student->parent) {
                $student->parent->update([
                    'name' => $parentName,
                ]);

                if ($student->parent->user) {
                    $student->parent->user->update([
                        'name' => $parentName ?: 'Orang Tua',
                        'phone' => $phone,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->update([
            'status' => 'hibernasi',
        ]);

        $student->delete();

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid dihibernasi.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:students,id'],
        ]);

        $count = Student::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->count();

        Student::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->update(['status' => 'hibernasi']);

        Student::whereIn('id', $validated['ids'])
            ->where('status', 'hibernasi')
            ->delete();

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.students.index')
            ->with('status', "{$count} murid berhasil dihibernasi.");
    }

    public function restore(int $studentId): RedirectResponse
    {
        $student = Student::withTrashed()->findOrFail($studentId);

        $student->restore();

        $student->update([
            'status' => 'active',
        ]);

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil dipulihkan.');
    }
}