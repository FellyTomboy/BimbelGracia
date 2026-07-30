<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
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

        $students = Student::with(['user', 'teachers']);

        $students = $this->applySearch($students, $params['search'], [
            'students.name',
            'user.phone',
            'students.whatsapp_primary',
            'students.status',
        ]);

        $students = $this->applySort($students, $params['sort'], $params['direction'], [
            'students.name', 'students.status', 'students.created_at',
        ]);

        $students = $students->paginate(20)->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function inactive(): View
    {
        $students = Student::withTrashed()
            ->where('status', 'hibernasi')
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
            'name' => ['required', 'string'],
            'whatsapp' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $defaultPassword = config('bimbel.default_password', '12345678');
        $phone = $validated['whatsapp'];

        // Parse names: one per line or comma-separated
        $names = $this->parseNames($validated['name']);

        $user = User::create([
            'name' => $names[0],
            'phone' => $phone,
            'role' => UserRole::Murid,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'name' => $names,
            'whatsapp' => $phone,
            'whatsapp_primary' => $phone,
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
            'name' => ['required', 'string'],
            'whatsapp' => ['required', 'string', 'max:32', 'unique:users,phone,'.$student->user_id],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $phone = $validated['whatsapp'];
        $names = $this->parseNames($validated['name']);

        $student->update([
            'name' => $names,
            'whatsapp' => $phone,
            'whatsapp_primary' => $phone,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        if ($student->user) {
            $student->user->update([
                'name' => $names[0],
                'phone' => $phone,
            ]);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil diperbarui.');
    }

    /**
     * Parse names from input string (one per line or comma-separated).
     */
    private function parseNames(string $input): array
    {
        // Split by newline first, then by comma
        $lines = preg_split('/\r\n|\r|\n/', $input);
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            // Also split by comma
            $parts = explode(',', $line);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    $names[] = $part;
                }
            }
        }
        return array_unique($names);
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