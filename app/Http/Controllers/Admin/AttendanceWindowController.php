<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceWindow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceWindowController extends Controller
{
    public function index(): View
    {
        $windows = AttendanceWindow::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('admin.attendance-windows.index', compact('windows'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        AttendanceWindow::updateOrCreate(
            [
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'is_open' => true,
                'opened_by' => $request->user()->id,
                'opened_at' => now(),
                'closed_at' => null,
            ]
        );

        return redirect()
            ->route('admin.attendance-windows.index')
            ->with('status', 'Periode presensi dibuka.');
    }

    public function close(AttendanceWindow $attendanceWindow): RedirectResponse
    {
        $attendanceWindow->update([
            'is_open' => false,
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('admin.attendance-windows.index')
            ->with('status', 'Periode presensi ditutup.');
    }
}
