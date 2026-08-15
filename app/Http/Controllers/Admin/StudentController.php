<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use App\Services\MonthlySnapshotSyncService;
use App\Traits\SearchAndSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $parents = ParentModel::with(['user', 'students'])->orderBy('name')->get();

        return view('admin.students.inactive', compact('students', 'parents'));
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->update([
            'status' => 'hibernasi',
        ]);

        $student->delete();

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.students.inactive')
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

    public function restore(Request $request, int $studentId): RedirectResponse
    {
        $student = Student::withTrashed()->findOrFail($studentId);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:parents,id'],
            'new_parent_name' => ['nullable', 'string', 'max:255'],
            'new_parent_phone' => ['nullable', 'string', 'max:20'],
        ]);

        // If student had a parent that's still active, restore to that parent
        $parentId = $validated['parent_id'] ?? $student->parent_id;

        // If no parent selected and no new parent info, try original parent
        if (! $parentId && blank($validated['new_parent_name']) && blank($validated['new_parent_phone'])) {
            $originalParent = ParentModel::withTrashed()->find($student->parent_id);
            if ($originalParent) {
                $parentId = $originalParent->id;
            } else {
                return back()->withErrors(['parent_id' => 'Pilih parent atau buat parent baru untuk murid ini.'])->withInput();
            }
        }

        // Create new parent if new info provided
        if (! $parentId && $validated['new_parent_phone']) {
            $parent = $this->createParent(
                $validated['new_parent_name'] ?? 'Orang Tua',
                $validated['new_parent_phone']
            );
            $parentId = $parent->id;
        }

        // Restore student
        $student->restore();
        $student->update([
            'status' => 'active',
            'parent_id' => $parentId,
        ]);

        // Also restore any enrollments that were hibernated along with the student
        $enrollmentCount = $student->enrollments()->onlyTrashed()->count();
        if ($enrollmentCount > 0) {
            $student->enrollments()->onlyTrashed()->update(['status' => 'active']);
            $student->enrollments()->onlyTrashed()->restore();
        }

        $this->snapshotSyncService->syncAll();

        $message = 'Murid berhasil dipulihkan.';
        if ($enrollmentCount > 0) {
            $message .= " {$enrollmentCount} enrollment yang hibernasi juga dipulihkan.";
        }

        return redirect()
            ->route('admin.students.inactive')
            ->with('status', $message);
    }

    private function createParent(string $name, string $phone): ParentModel
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (! str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        $defaultPassword = config('bimbel.default_password', 'password');

        $user = \App\Models\User::create([
            'name' => $name,
            'phone' => $phone,
            'role' => \App\Enums\UserRole::Parent,
            'password' => \Illuminate\Support\Facades\Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        return ParentModel::create([
            'user_id' => $user->id,
            'name' => $name,
        ]);
    }
}