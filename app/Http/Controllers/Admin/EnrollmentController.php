<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\MonthlySnapshotSyncService;
use Illuminate\Support\Facades\DB;
use App\Traits\SearchAndSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    use SearchAndSort;

    public function __construct(private MonthlySnapshotSyncService $snapshotSyncService)
    {
    }
    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);
        $activeTab = $request->query('type', 'kelas');

        $buildQuery = function (string $type) use ($params) {
            $q = Enrollment::query()
                ->where('type', $type)
                ->with(['program', 'teacher', 'students']);

            $q = $this->applySearch($q, $params['search'], [
                'program.name',
                'teacher.name',
                'enrollments.status',
                'students.full_name',
                'students.nickname',
            ]);

            return $this->applyEnrollmentSort($q, $params['sort'], $params['direction']);
        };

        $kelasEnrollments = (clone $buildQuery('kelas'))->paginate(20)->withQueryString();
        $privatEnrollments = (clone $buildQuery('privat'))->paginate(20)->withQueryString();

        return view('admin.enrollments.index', compact('kelasEnrollments', 'privatEnrollments', 'activeTab'));
    }

    public function inactive(): View
    {
        $enrollments = Enrollment::withTrashed()
            ->where('status', 'hibernasi')
            ->with(['program', 'teacher', 'students'])
            ->latest('deleted_at')
            ->get();

        return view('admin.enrollments.inactive', compact('enrollments'));
    }

    public function create(Request $request): View
    {
        $programs = Program::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();
        $defaultType = $request->query('type', 'privat');

        return view('admin.enrollments.create', compact('programs', 'teachers', 'students', 'defaultType'));
    }

    public function isKelasMode(Request $request): bool
    {
        if ($request->input('type') !== 'kelas') {
            return false;
        }

        $program = Program::find($request->input('program_id'));

        return $program && $program->type === 'kelas';
    }

    public function store(Request $request): RedirectResponse
    {
        $isKelas = $this->isKelasMode($request);
        $studentIds = $request->input('student_ids', []);

        $rules = [
            'program_id' => ['required', 'exists:programs,id'],
            'type' => ['required', 'in:privat,kelas'],
            'teacher_id' => [
                $isKelas ? 'nullable' : 'required',
                'exists:teachers,id',
            ],
            'parent_rate' => [
                (count($studentIds) > 1) ? 'nullable' : 'required',
                'integer',
                'min:0',
            ],
            'teacher_rate' => [
                ($isKelas || count($studentIds) > 1) ? 'nullable' : 'required',
                'integer',
                'min:0',
            ],
            'pricing_tiers_parent' => ['nullable', 'array'],
            'pricing_tiers_parent.*' => ['nullable', 'numeric', 'min:0'],
            'pricing_tiers_teacher' => ['nullable', 'array'],
            'pricing_tiers_teacher.*' => ['nullable', 'numeric', 'min:0'],
            'agreed_sessions_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'status' => ['required', 'in:active,hibernasi'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];

        // For kelas mode, only allow 1 student
        if ($isKelas) {
            $rules['student_ids'] = ['required', 'array', 'min:1', 'max:1'];
        }

        $validated = $request->validate($rules, [], $this->validationAttributes());

        // Ensure default values when fields are hidden
        if ($isKelas) {
            $validated['teacher_rate'] = $validated['teacher_rate'] ?? 0;
        } elseif (count($studentIds) > 1) {
            $validated['teacher_rate'] = $validated['teacher_rate'] ?? 0;
            $validated['parent_rate'] = $validated['parent_rate'] ?? 0;
        }

        // Build pricing_tiers from request or fallback to single-tier
        $pricingTiers = null;
        if ($request->has('pricing_tiers_parent')) {
            $pricingTiers = [
                'parent_rate' => $validated['pricing_tiers_parent'] ?? ['1' => $validated['parent_rate']],
                'teacher_rate' => $validated['pricing_tiers_teacher'] ?? ['1' => $validated['teacher_rate']],
            ];
            // Ensure keys are strings
            if (isset($pricingTiers['parent_rate'])) {
                $pricingTiers['parent_rate'] = array_combine(
                    array_map('strval', array_keys($pricingTiers['parent_rate'])),
                    array_values($pricingTiers['parent_rate'])
                );
            }
            if (isset($pricingTiers['teacher_rate'])) {
                $pricingTiers['teacher_rate'] = array_combine(
                    array_map('strval', array_keys($pricingTiers['teacher_rate'])),
                    array_values($pricingTiers['teacher_rate'])
                );
            }
        }

        $enrollment = Enrollment::create([
            'program_id' => $validated['program_id'],
            'type' => $validated['type'],
            'teacher_id' => $validated['teacher_id'] ?? null,
            'parent_rate' => $validated['parent_rate'],
            'teacher_rate' => $validated['teacher_rate'],
            'pricing_tiers' => $pricingTiers,
            'agreed_sessions_per_month' => $validated['agreed_sessions_per_month'],
            'validation_status' => 0,
            'status' => $validated['status'],
        ]);

        $enrollment->students()->sync($validated['student_ids']);

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.enrollments.index', ['type' => $validated['type']])
            ->with('status', 'Enrollment berhasil dibuat.');
    }

    public function edit(Enrollment $enrollment): View
    {
        $enrollment->load('students');
        $programs = Program::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        return view('admin.enrollments.edit', compact('enrollment', 'programs', 'teachers', 'students'));
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $isKelas = $this->isKelasMode($request);
        $studentIds = $request->input('student_ids', []);

        $rules = [
            'program_id' => ['required', 'exists:programs,id'],
            'type' => ['required', 'in:privat,kelas'],
            'teacher_id' => [
                $isKelas ? 'nullable' : 'required',
                'exists:teachers,id',
            ],
            'parent_rate' => [
                (count($studentIds) > 1) ? 'nullable' : 'required',
                'integer',
                'min:0',
            ],
            'teacher_rate' => [
                ($isKelas || count($studentIds) > 1) ? 'nullable' : 'required',
                'integer',
                'min:0',
            ],
            'pricing_tiers_parent' => ['nullable', 'array'],
            'pricing_tiers_parent.*' => ['nullable', 'numeric', 'min:0'],
            'pricing_tiers_teacher' => ['nullable', 'array'],
            'pricing_tiers_teacher.*' => ['nullable', 'numeric', 'min:0'],
            'agreed_sessions_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'status' => ['required', 'in:active,hibernasi'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];

        // For kelas mode, only allow 1 student
        if ($isKelas) {
            $rules['student_ids'] = ['required', 'array', 'min:1', 'max:1'];
        }

        $validated = $request->validate($rules, [], $this->validationAttributes());

        // Ensure default values when fields are hidden
        if ($isKelas) {
            $validated['teacher_rate'] = $validated['teacher_rate'] ?? 0;
        } elseif (count($studentIds) > 1) {
            $validated['teacher_rate'] = $validated['teacher_rate'] ?? 0;
            $validated['parent_rate'] = $validated['parent_rate'] ?? 0;
        }

        // Build pricing_tiers from request or fallback to single-tier
        $pricingTiers = null;
        if ($request->has('pricing_tiers_parent')) {
            $pricingTiers = [
                'parent_rate' => $validated['pricing_tiers_parent'] ?? ['1' => $validated['parent_rate']],
                'teacher_rate' => $validated['pricing_tiers_teacher'] ?? ['1' => $validated['teacher_rate']],
            ];
            if (isset($pricingTiers['parent_rate'])) {
                $pricingTiers['parent_rate'] = array_combine(
                    array_map('strval', array_keys($pricingTiers['parent_rate'])),
                    array_values($pricingTiers['parent_rate'])
                );
            }
            if (isset($pricingTiers['teacher_rate'])) {
                $pricingTiers['teacher_rate'] = array_combine(
                    array_map('strval', array_keys($pricingTiers['teacher_rate'])),
                    array_values($pricingTiers['teacher_rate'])
                );
            }
        }

        $enrollment->update([
            'program_id' => $validated['program_id'],
            'type' => $validated['type'],
            'teacher_id' => $validated['teacher_id'] ?? null,
            'parent_rate' => $validated['parent_rate'],
            'teacher_rate' => $validated['teacher_rate'],
            'pricing_tiers' => $pricingTiers,
            'agreed_sessions_per_month' => $validated['agreed_sessions_per_month'],
            'status' => $validated['status'],
        ]);

        $enrollment->students()->sync($validated['student_ids']);

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment berhasil diperbarui.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        // simpan id untuk sink snapshot setelah delete
        $enrollmentId = $enrollment->id;

        $enrollment->update([
            'status' => 'hibernasi',
        ]);

        $enrollment->delete();

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment dihibernasi.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:enrollments,id'],
        ]);

        $count = Enrollment::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->count();

        Enrollment::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->update(['status' => 'hibernasi']);

        // Only delete enrollments that no longer have active attendance records.
        // Enrollments that have newly created attendances (race between step 1 and 2)
        // are kept but remain hibernated — attendances stay valid.
        Enrollment::whereIn('id', $validated['ids'])
            ->where('status', 'hibernasi')
            ->whereDoesntHave('attendances')
            ->delete();

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', "{$count} enrollment berhasil dihibernasi.");
    }

    public function restore(int $enrollmentId): RedirectResponse
    {
        $enrollment = Enrollment::withTrashed()->findOrFail($enrollmentId);
        $enrollment->restore();

        $enrollment->update([
            'status' => 'active',
        ]);

        $this->snapshotSyncService->syncAll();

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Enrollment berhasil dipulihkan.');
    }

    /**
     * Friendlier Indonesian labels for validation error messages.
     */
    private function validationAttributes(): array
    {
        return [
            'program_id' => 'program',
            'type' => 'tipe enrollment',
            'teacher_id' => 'guru',
            'parent_rate' => 'harga orang tua',
            'teacher_rate' => 'gaji guru',
            'pricing_tiers_parent' => 'harga orang tua bertingkat',
            'pricing_tiers_teacher' => 'gaji guru bertingkat',
            'agreed_sessions_per_month' => 'janji sesi per bulan',
            'status' => 'status',
            'student_ids' => 'murid',
        ];
    }

    private function applyEnrollmentSort(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if (! $sort) {
            return $query->latest();
        }

        return match ($sort) {
            'students.name' => $query
                ->select('enrollments.*')
                ->selectSub(
                    Student::withTrashed()
                        ->selectRaw('MIN(COALESCE(full_name, nickname))')
                        ->join('enrollment_student', 'students.id', '=', 'enrollment_student.student_id')
                        ->whereColumn('enrollment_student.enrollment_id', 'enrollments.id'),
                    'student_sort_name'
                )
                ->orderBy('student_sort_name', $direction)
                ->orderByDesc('enrollments.created_at'),
            'teachers.name' => $query
                ->select('enrollments.*')
                ->selectSub(
                    Teacher::withTrashed()
                        ->select('name')
                        ->whereColumn('teachers.id', 'enrollments.teacher_id')
                        ->limit(1),
                    'teacher_sort_name'
                )
                ->orderBy('teacher_sort_name', $direction)
                ->orderByDesc('enrollments.created_at'),
            'programs.name' => $query
                ->select('enrollments.*')
                ->selectSub(
                    Program::withTrashed()
                        ->select('name')
                        ->whereColumn('programs.id', 'enrollments.program_id')
                        ->limit(1),
                    'program_sort_name'
                )
                ->orderBy('program_sort_name', $direction)
                ->orderByDesc('enrollments.created_at'),
            'enrollments.parent_rate', 'enrollments.teacher_rate', 'enrollments.validation_status', 'enrollments.status', 'enrollments.created_at' => $query->orderBy($sort, $direction),
            default => $query->latest(),
        };
    }

}
