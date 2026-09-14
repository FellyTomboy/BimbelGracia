<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use Illuminate\Http\JsonResponse;
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

    public function previewUpholdConfirm(MonthlyAttendance $attendance): JsonResponse
    {
        abort_unless($attendance->parent_review_status === 'pending', 404);

        $html = view('admin.notifications._uphold-confirm', compact('attendance'))->render();

        return response()->json([
            'html' => $html,
            'title' => 'Konfirmasi Penolakan Presensi',
        ]);
    }

    public function upholdParentRejection(Request $request, MonthlyAttendance $attendance): JsonResponse|RedirectResponse
    {
        abort_unless($attendance->parent_review_status === 'pending', 404);

        $attendance->update([
            'parent_review_status' => 'rejected',
            'status_validation' => 'ditolak',
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Penolakan dikonfirmasi. Status presensi sekarang ditolak.']);
        }

        return $this->backWithQueryString('Penolakan dikonfirmasi. Status presensi sekarang ditolak.');
    }

    public function dismiss(Request $request, MonthlyAttendance $attendance): JsonResponse|RedirectResponse
    {
        abort_unless($attendance->parent_review_status === 'pending', 404);

        $attendance->update([
            'parent_review_status' => 'dismissed',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Penolakan dibatalkan. Status presensi tidak diubah.']);
        }

        return $this->backWithQueryString('Penolakan dibatalkan. Status presensi tidak diubah.');
    }
}
