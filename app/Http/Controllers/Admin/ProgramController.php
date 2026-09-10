<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherProgramRate;
use App\Services\MonthlySnapshotSyncService;
use App\Traits\SearchAndSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramController extends Controller
{
    use SearchAndSort;

    public function __construct(private MonthlySnapshotSyncService $snapshotSyncService)
    {
    }
    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $programs = Program::query();

        $programs = $this->applySearch($programs, $params['search'], [
            'name', 'type', 'subject', 'status',
        ]);

        $programs = $this->applySort($programs, $params['sort'], $params['direction'], [
            'name', 'type', 'status', 'default_parent_rate', 'default_teacher_rate', 'created_at',
        ]);

        $programs = $programs->paginate(20)->withQueryString();

        return view('admin.programs.index', compact('programs'));
    }

    public function inactive(): View
    {
        $programs = Program::withTrashed()
            ->where('status', 'hibernasi')
            ->latest('deleted_at')
            ->get();

        return view('admin.programs.inactive', compact('programs'));
    }

    public function create(): View
    {
        $teachers = Teacher::orderBy('full_name')->get();
        return view('admin.programs.create', compact('teachers'));
    }

    public function createForm(Request $request): JsonResponse
    {
        $teachers = Teacher::orderBy('full_name')->get();
        $html = view('admin.programs._form', [
            'program' => null,
            'teachers' => $teachers,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Tambah Program Les',
        ]);
    }

    public function editForm(Request $request, Program $program): JsonResponse
    {
        $program->load('teachers');
        $teachers = Teacher::orderBy('full_name')->get();
        $html = view('admin.programs._form', [
            'program' => $program,
            'teachers' => $teachers,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Edit Program Les — ' . $program->name,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:privat,kelas'],
            'division' => ['nullable', 'string', 'in:TK,SD,SMP,SMA,mengaji,mix'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_rate_privat' => ['nullable', 'integer', 'min:0'],
            'teacher_rate_privat' => ['nullable', 'integer', 'min:0'],
            'parent_rate_kelas' => ['nullable', 'integer', 'min:0'],
            'teacher_rate_kelas' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $type = $validated['type'];
        $validated['default_parent_rate'] = (int) ($type === 'privat'
            ? ($validated['parent_rate_privat'] ?? 0)
            : ($validated['parent_rate_kelas'] ?? 0));
        $validated['default_teacher_rate'] = (int) ($type === 'privat'
            ? ($validated['teacher_rate_privat'] ?? 0)
            : ($validated['teacher_rate_kelas'] ?? 0));

        if ($type === 'privat') {
            $validated['default_teacher_rate'] = max(1, $validated['default_teacher_rate']);
        }

        $program = Program::create($validated);

        if ($type === 'kelas') {
            $this->syncTeacherRates($program, $request->input('teacher_rates', []));
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Program berhasil dibuat.',
                'program' => $program,
            ]);
        }

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil dibuat.');
    }

    public function edit(Program $program): View
    {
        $program->load('teachers');
        $teachers = Teacher::orderBy('full_name')->get();
        return view('admin.programs.edit', compact('program', 'teachers'));
    }

    public function update(Request $request, Program $program): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:privat,kelas'],
            'division' => ['nullable', 'string', 'in:TK,SD,SMP,SMA,mengaji,mix'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_rate_privat' => ['nullable', 'integer', 'min:0'],
            'teacher_rate_privat' => ['nullable', 'integer', 'min:0'],
            'parent_rate_kelas' => ['nullable', 'integer', 'min:0'],
            'teacher_rate_kelas' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $type = $validated['type'];
        $validated['default_parent_rate'] = (int) ($type === 'privat'
            ? ($validated['parent_rate_privat'] ?? 0)
            : ($validated['parent_rate_kelas'] ?? 0));
        $validated['default_teacher_rate'] = (int) ($type === 'privat'
            ? ($validated['teacher_rate_privat'] ?? 0)
            : ($validated['teacher_rate_kelas'] ?? 0));

        if ($type === 'privat') {
            $validated['default_teacher_rate'] = max(1, $validated['default_teacher_rate']);
        }

        $program->update($validated);

        if ($type === 'kelas') {
            $this->syncTeacherRates($program, $request->input('teacher_rates', []));
        } else {
            $program->teacherRates()->delete();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Program berhasil diperbarui.',
                'program' => $program,
            ]);
        }

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil diperbarui.');
    }

    public function destroy(Request $request, Program $program): JsonResponse|RedirectResponse
    {
        $program->update([
            'status' => 'hibernasi',
        ]);

        $program->delete();

        $this->snapshotSyncService->syncAll();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Program dihibernasi.',
            ]);
        }

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program dihibernasi.');
    }

    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:programs,id'],
        ]);

        $count = Program::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->count();

        Program::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->update(['status' => 'hibernasi']);

        Program::whereIn('id', $validated['ids'])
            ->where('status', 'hibernasi')
            ->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} program berhasil dihibernasi.",
                'count' => $count,
            ]);
        }

        return redirect()
            ->route('admin.programs.index')
            ->with('status', "{$count} program berhasil dihibernasi.");
    }

    public function restore(Request $request): JsonResponse|RedirectResponse
    {
        $programId = $request->route('program');
        $program = Program::withTrashed()->findOrFail($programId);

        $program->restore();

        $program->update([
            'status' => 'active',
        ]);

        $this->snapshotSyncService->syncAll();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Program berhasil dipulihkan.',
            ]);
        }

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil dipulihkan.');
    }

    private function syncTeacherRates(Program $program, array $teacherRates): void
    {
        $program->teacherRates()->delete();

        foreach ($teacherRates as $teacherId => $rate) {
            $rateInt = (int) ($rate ?? 0);
            if ($rateInt <= 0) {
                continue;
            }
            TeacherProgramRate::create([
                'teacher_id' => (int) $teacherId,
                'program_id' => $program->id,
                'rate' => $rateInt,
            ]);
        }
    }
}
