<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Services\MonthlySnapshotSyncService;
use Carbon\Carbon;
use App\Traits\SearchAndSort;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
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

        $currentMonth = (int) Carbon::now()->month;
        $currentYear = (int) Carbon::now()->year;

        $teachers = Teacher::with('user')
            ->withCount([
                'enrollmentAttendances as attendance_count_this_month' => function ($query) use ($currentMonth, $currentYear) {
                    $query->where('month', $currentMonth)
                          ->where('year', $currentYear);
                },
            ]);

        $teachers = $this->applySearch($teachers, $params['search'], [
            'teachers.full_name',
            'teachers.nickname',
            'user.phone',
            'teachers.whatsapp_number',
            'teachers.major',
            'teachers.subjects',
            'teachers.status',
        ]);

        $teachers = $this->applySort($teachers, $params['sort'], $params['direction'], [
            'teachers.full_name', 'teachers.class_rate', 'teachers.status', 'teachers.created_at',
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
            'nickname' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:255'],
            'subjects' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account' => ['required', 'string', 'max:255'],
            'bank_owner' => ['required', 'string', 'max:255'],
            'class_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $fullName = trim((string) ($validated['full_name'] ?? '')) ?: null;
        $nickname = trim($validated['nickname']);

        $teacher->update([
            'full_name' => $fullName,
            'nickname' => $nickname,
            'major' => trim($validated['major']),
            'subjects' => trim($validated['subjects']),
            'address' => trim($validated['address']),
            'bank_name' => trim($validated['bank_name']),
            'bank_account' => trim($validated['bank_account']),
            'bank_owner' => trim($validated['bank_owner']),
            'class_rate' => $validated['class_rate'],
            'status' => $validated['status'],
        ]);

        if ($teacher->user) {
            $teacher->user->update([
                'name' => $teacher->full_name,
            ]);
        }

        $redirectTo = $request->input('redirect_to', route('guru.salary-projection.index'));

        return redirect()->to($redirectTo)
            ->with('status', 'Data guru berhasil diperbarui.');
    }

    public function completeDataPublic(Request $request, int $teacher): View
    {
        $teacherModel = Teacher::findOrFail($teacher);

        return view('guru.complete-data-public', [
            'teacher' => $teacherModel,
            'redirect_to' => $request->query('redirect_to', route('guru.salary-projection.index')),
        ]);
    }

    public function submitCompleteDataPublic(Request $request, int $teacher): RedirectResponse
    {
        $teacherModel = Teacher::findOrFail($teacher);

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:255'],
            'subjects' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account' => ['required', 'string', 'max:255'],
            'bank_owner' => ['required', 'string', 'max:255'],
            'class_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $fullName = trim((string) ($validated['full_name'] ?? '')) ?: null;
        $teacherModel->update([
            'full_name' => $fullName,
            'nickname' => trim($validated['nickname']),
            'major' => trim($validated['major']),
            'subjects' => trim($validated['subjects']),
            'address' => trim($validated['address']),
            'bank_name' => trim($validated['bank_name']),
            'bank_account' => trim($validated['bank_account']),
            'bank_owner' => trim($validated['bank_owner']),
            'class_rate' => $validated['class_rate'],
            'status' => $validated['status'],
        ]);

        if ($teacherModel->user) {
            $teacherModel->user->update(['name' => $teacherModel->full_name]);
        }

        $redirectTo = $request->input('redirect_to', route('guru.salary-projection.index'));

        return redirect()->to($redirectTo)
            ->with('status', 'Data guru berhasil diperbarui.');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
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

        $fullName = trim((string) ($validated['full_name'] ?? '')) ?: null;
        $nickname = trim((string) ($validated['nickname'] ?? '')) ?: null;

        $defaultPassword = config('bimbel.default_password', 'password');
        $phone = $this->cleanPhone($validated['whatsapp']);

        $user = User::create([
            'name' => $fullName ?: $nickname ?: 'Guru',
            'phone' => $phone,
            'role' => UserRole::Guru,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'full_name' => $fullName ?: $nickname ?: 'Guru',
            'nickname' => $nickname,
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Guru berhasil dibuat.']);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil dibuat.');
    }

    public function createForm(Request $request): JsonResponse
    {
        $html = view('admin.teachers._form', [
            'teacher' => null,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Tambah Guru',
        ]);
    }

    public function editForm(Request $request, Teacher $teacher): JsonResponse
    {
        $html = view('admin.teachers._form', [
            'teacher' => $teacher,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Edit Guru',
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
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

        $fullName = trim((string) ($validated['full_name'] ?? '')) ?: null;
        $nickname = trim((string) ($validated['nickname'] ?? '')) ?: null;
        $phone = $this->cleanPhone($validated['whatsapp']);

        $teacher->update([
            'full_name' => $fullName ?: $nickname ?: $teacher->full_name ?: 'Guru',
            'nickname' => $nickname,
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
                'name' => $fullName ?: $nickname ?: $teacher->full_name ?: 'Guru',
                'phone' => $phone,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Guru berhasil diperbarui.']);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): JsonResponse|RedirectResponse
    {
        $teacher->update([
            'status' => 'hibernasi',
        ]);

        $teacher->user->delete();
        $teacher->delete();

        $this->snapshotSyncService->syncAll();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Guru dihibernasi.']);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru dihibernasi.');
    }

    public function approvePhoto(Request $request, Teacher $teacher): JsonResponse|RedirectResponse
    {
        $teacher->update(['profile_photo_approved' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Foto profil guru berhasil disetujui.']);
        }

        return redirect()->route('admin.teachers.index')
            ->with('status', 'Foto profile guru berhasil disetujui.');
    }

    public function changePassword(Request $request, Teacher $teacher): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $teacher->user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password guru berhasil diubah.']);
        }

        return redirect()->route('admin.teachers.edit', $teacher)
            ->with('status', 'Password guru berhasil diubah.');
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

    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:teachers,id'],
        ]);

        $count = Teacher::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->count();

        $teacherIds = $validated['ids'];

        Teacher::whereIn('id', $teacherIds)
            ->where('status', 'active')
            ->update(['status' => 'hibernasi']);

        $userIds = Teacher::whereIn('id', $teacherIds)
            ->where('status', 'hibernasi')
            ->pluck('user_id');

        User::whereIn('id', $userIds)->delete();

        Teacher::whereIn('id', $teacherIds)
            ->where('status', 'hibernasi')
            ->delete();

        $this->snapshotSyncService->syncAll();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "{$count} guru berhasil dihibernasi."]);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', "{$count} guru berhasil dihibernasi.");
    }

    public function restore(Request $request, int $teacherId): JsonResponse|RedirectResponse
    {
        $teacher = Teacher::withTrashed()->findOrFail($teacherId);

        $user = User::withTrashed()->find($teacher->user_id);
        if ($user) {
            $user->restore();
        }

        $teacher->restore();

        $teacher->update([
            'status' => 'active',
        ]);

        $this->snapshotSyncService->syncAll();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Guru berhasil dipulihkan.']);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil dipulihkan.');
    }
}