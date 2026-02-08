<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::query()
            ->latest()
            ->get();

        return view('admin.programs.index', compact('programs'));
    }

    public function inactive(): View
    {
        $programs = Program::onlyTrashed()
            ->latest('deleted_at')
            ->get();

        return view('admin.programs.inactive', compact('programs'));
    }

    public function create(): View
    {
        return view('admin.programs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:privat,kelompok,online'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_parent_rate' => ['required', 'integer', 'min:0'],
            'default_teacher_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        Program::create($validated);

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil dibuat.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.edit', compact('program'));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:privat,kelompok,online'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_parent_rate' => ['required', 'integer', 'min:0'],
            'default_teacher_rate' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $program->update($validated);

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $program->delete();

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program dihibernasi.');
    }

    public function restore(int $programId): RedirectResponse
    {
        $program = Program::withTrashed()->findOrFail($programId);
        $program->restore();

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil dipulihkan.');
    }
}
