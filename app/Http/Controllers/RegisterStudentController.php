<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NewStudent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterStudentController extends Controller
{
    public function form(string $token): View
    {
        $newStudent = NewStudent::where('token', $token)->firstOrFail();

        if ($newStudent->converted) {
            return view('register-student.closed');
        }

        return view('register-student.form', compact('newStudent'));
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $newStudent = NewStudent::where('token', $token)->firstOrFail();

        if ($newStudent->converted) {
            return back()->with('status', 'Form ini sudah tidak aktif.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_whatsapp' => ['nullable', 'string', 'max:20'],
            'school' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $newStudent->update($validated);

        return redirect()
            ->route('register-student.success')
            ->with('status', 'Data berhasil dikirim.');
    }

    public function success(): View
    {
        return view('register-student.success');
    }
}