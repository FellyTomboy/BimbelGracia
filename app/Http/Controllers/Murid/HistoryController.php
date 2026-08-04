<?php

declare(strict_types=1);

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $parent = $request->user()?->parent;
        $student = $parent?->students()->first();

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when($student, fn ($query) => $query->whereHas('students', fn ($sub) => $sub->where('students.id', $student->id)))
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('murid.history.index', [
            'month' => $month,
            'year' => $year,
            'student' => $student,
            'attendances' => $attendances,
        ]);
    }

    public function reject(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $parent = $request->user()?->parent;
        $student = $parent?->students()->first();

        abort_unless($student && $attendance->students()->whereKey($student->id)->exists(), 403);

        if ($attendance->parent_review_status === 'pending') {
            return back()->with('status', 'Penolakan presensi sudah dikirim dan menunggu konfirmasi admin.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $attendance->update([
            'parent_review_status' => 'pending',
            'parent_reviewed_at' => now(),
            'parent_rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'Penolakan presensi terkirim. Silakan hubungi admin untuk konfirmasi penolakan.');
    }

    public function cancelReject(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $parent = $request->user()?->parent;
        $student = $parent?->students()->first();

        abort_unless($student && $attendance->students()->whereKey($student->id)->exists(), 403);

        abort_unless($attendance->parent_review_status === 'pending', 404);

        $attendance->update([
            'parent_review_status' => null,
            'parent_reviewed_at' => null,
            'parent_rejection_reason' => null,
        ]);

        return back()->with('status', 'Penolakan presensi dibatalkan. Antrian konfirmasi admin telah dihapus.');
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
    }
}