<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Enrollment;
use App\Services\MonthlySnapshotSyncService;
use App\Traits\SearchAndSort;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherController extends Controller
{
    use SearchAndSort;

    public function __construct(private MonthlySnapshotSyncService $snapshotSyncService)
    {
    }
    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $teachers = Teacher::with('user');

        $teachers = $this->applySearch($teachers, $params['search'], [
            'teachers.name',
            'user.phone',
            'teachers.whatsapp_number',
            'teachers.major',
            'teachers.subjects',
            'teachers.status',
        ]);

        $teachers = $this->applySort($teachers, $params['sort'], $params['direction'], [
            'teachers.name', 'teachers.class_rate', 'teachers.status', 'teachers.created_at',
        ]);

        $teachers = $teachers->paginate(20)->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function inactive(): View
    {
        $teachers = Teacher::withTrashed()
            ->where('status', 'hibernasi')
            ->with('user')
            ->latest('deleted_at')
            ->get();

        return view('admin.teachers.inactive', compact('teachers'));
    }

    public function create(): View
    {
        return view('admin.teachers.create');
    }

    public function completeData(Request $request): View
    {
        $teacher = $request->user()?->teacher;
        abort_unless($teacher, 403);

        return view('guru.complete-data', [
            'teacher' => $teacher,
            'redirect_to' => $request->query('redirect_to', route('guru.salary-projection.index')),
        ]);
    }

    public function submitCompleteData(Request $request): RedirectResponse
    {
        $teacher = $request->user()?->teacher;
        abort_unless($teacher, 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
            'class_rate' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,hibernasi'],
        ]);

        $name = trim((string) ($validated['name'] ?? '')) ?: null;
        $nickname = trim((string) ($validated['nickname'] ?? '')) ?: null;
        $fullName = trim((string) ($validated['full_name'] ?? '')) ?: null;

        $teacher->update([
            'name' => $name ?: $fullName ?: $nickname ?: $teacher->name ?: 'Guru',
            'nickname' => $nickname,
            'full_name' => $fullName,
            'major' => trim((string) ($validated['major'] ?? '')) ?: null,
            'subjects' => trim((string) ($validated['subjects'] ?? '')) ?: null,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'bank_name' => trim((string) ($validated['bank_name'] ?? '')) ?: null,
            'bank_account' => trim((string) ($validated['bank_account'] ?? '')) ?: null,
            'bank_owner' => trim((string) ($validated['bank_owner'] ?? '')) ?: null,
            'class_rate' => $validated['class_rate'] ?? $teacher->class_rate,
            'status' => $validated['status'] ?? $teacher->status,
        ]);

        if ($teacher->user) {
            $teacher->user->update([
                'name' => $teacher->name,
            ]);
        }

        $redirectTo = $request->input('redirect_to', route('guru.salary-projection.index'));

        return redirect()->to($redirectTo)
            ->with('status', 'Data guru berhasil diperbarui.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'major' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
            'class_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $displayName = trim((string) ($validated['full_name'] ?? '')) ?: trim((string) ($validated['name'] ?? '')) ?: null;
        $nickname = trim((string) ($validated['nickname'] ?? '')) ?: $displayName;

        $defaultPassword = config('bimbel.default_password', '12345678');
        $phone = $validated['whatsapp'];

        $user = User::create([
            'name' => $displayName ?: 'Guru',
            'phone' => $phone,
            'role' => UserRole::Guru,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'name' => $displayName ?: $nickname ?: 'Guru',
            'nickname' => $nickname,
            'full_name' => $displayName,
            'whatsapp' => $phone,
            'whatsapp_number' => $phone,
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
            'name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:32', 'unique:users,phone,'.$teacher->user_id],
            'major' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
            'class_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $displayName = trim((string) ($validated['full_name'] ?? '')) ?: trim((string) ($validated['name'] ?? '')) ?: $teacher->full_name ?: $teacher->name ?: $teacher->nickname;
        $nickname = trim((string) ($validated['nickname'] ?? '')) ?: $teacher->nickname ?: $displayName;
        $phone = $validated['whatsapp'];

        $teacher->update([
            'name' => $displayName ?: $teacher->name ?: 'Guru',
            'nickname' => $nickname,
            'full_name' => $displayName,
            'whatsapp' => $phone,
            'whatsapp_number' => $phone,
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
                'name' => $displayName ?: $teacher->name ?: 'Guru',
                'phone' => $phone,
            ]);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->update([
            'status' => 'hibernasi',
        ]);

        $teacher->delete();

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru dihibernasi.');
    }

    public function approvePhoto(Request $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update(['profile_photo_approved' => true]);

        return redirect()->route('admin.teachers.index')
            ->with('status', 'Foto profile guru berhasil disetujui.');
    }

    public function changePassword(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $teacher->user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('status', 'Password guru berhasil diubah.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:teachers,id'],
        ]);

        $count = Teacher::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->count();

        Teacher::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->update(['status' => 'hibernasi']);

        Teacher::whereIn('id', $validated['ids'])
            ->where('status', 'hibernasi')
            ->delete();

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', "{$count} guru berhasil dihibernasi.");
    }

    public function restore(int $teacherId): RedirectResponse
    {
        $teacher = Teacher::withTrashed()->findOrFail($teacherId);

        $teacher->restore();

        $teacher->update([
            'status' => 'active',
        ]);

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil dipulihkan.');
    }
}