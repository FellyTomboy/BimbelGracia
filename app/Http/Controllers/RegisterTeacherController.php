<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TeacherRegistrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterTeacherController extends Controller
{
    public function form(string $token): View
    {
        return view('register-teacher.form');
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'major' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
        ]);

        TeacherRegistrant::create([
            'name' => $validated['name'],
            'whatsapp' => $this->cleanPhone($validated['whatsapp']),
            'major' => $validated['major'] ?? null,
            'subjects' => $validated['subjects'] ?? null,
            'address' => $validated['address'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account' => $validated['bank_account'] ?? null,
            'bank_owner' => $validated['bank_owner'] ?? null,
        ]);

        return redirect()
            ->route('register-teacher.success')
            ->with('status', 'Data berhasil dikirim.');
    }

    public function success(): View
    {
        return view('register-teacher.success');
    }

    private function cleanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Keep as 08XXXXXXXXX format in database
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
}
