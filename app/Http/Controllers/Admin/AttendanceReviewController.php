<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->where('parent_review_status', 'pending');

        $attendances = $query
            ->orderByDesc('parent_reviewed_at')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('admin.notifications.index', compact('attendances'));
    }

    public function confirm(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        abort_unless($attendance->parent_review_status === 'pending', 404);

        $attendance->update([
            'parent_review_status' => 'rejected',
            'status_validation' => 'ditolak',
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Penolakan orangtua dikonfirmasi. Status presensi sekarang ditolak.');
    }

    public function dismiss(MonthlyAttendance $attendance): RedirectResponse
    {
        abort_unless($attendance->parent_review_status === 'pending', 404);

        $attendance->update([
            'parent_review_status' => 'dismissed',
        ]);

        return back()->with('status', 'Penolakan orangtua dibatalkan. Status presensi tidak diubah.');
    }
}
