<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Traits\SearchAndSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassStudentController extends Controller
{
    use SearchAndSort;

    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $students = ClassStudent::query()
            ->where('status', 'active');

        $students = $this->applySearch($students, $params['search'], [
            'name', 'whatsapp_primary', 'whatsapp_secondary', 'status',
        ]);

        $students = $this->applySort($students, $params['sort'], $params['direction'], [
            'name', 'rate_per_meeting', 'status', 'created_at',
        ]);

        $students = $students->paginate(20)->withQueryString();

        return view('admin.class-students.index', compact('students'));
    }

    public function inactive(): View
    {
        $students = ClassStudent::withTrashed()
            ->where('status', 'hibernasi')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('admin.class-students.inactive', compact('students'));
    }

    public function create(): View
    {
        return view('admin.class-students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_primary' => ['required', 'string', 'max:32'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:32'],
            'rate_per_meeting' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
            'notes' => ['nullable', 'string'],
        ]);

        ClassStudent::create($validated);

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama berhasil dibuat.');
    }

    public function edit(ClassStudent $classStudent): View
    {
        return view('admin.class-students.edit', compact('classStudent'));
    }

    public function update(Request $request, ClassStudent $classStudent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_primary' => ['required', 'string', 'max:32'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:32'],
            'rate_per_meeting' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
            'notes' => ['nullable', 'string'],
        ]);

        $classStudent->update($validated);

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama berhasil diperbarui.');
    }

    public function destroy(ClassStudent $classStudent): RedirectResponse
    {
        $classStudent->update([
            'status' => 'hibernasi',
        ]);

        $classStudent->delete();

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama dihibernasi.');
    }

    public function restore(int $classStudentId): RedirectResponse
    {
        $classStudent = ClassStudent::withTrashed()->findOrFail($classStudentId);
        $classStudent->restore();

        $classStudent->update([
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.class-students.index')
            ->with('status', 'Murid kelas bersama dipulihkan.');
    }
}
