<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Services\Pdf\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $parent = $request->user()?->parent;
        $students = $parent?->students ?? collect();

        // Get all attendances for ALL students of this parent
        $studentIds = $students->pluck('id')->toArray();

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when(!empty($studentIds), fn ($query) => $query->whereHas('students', fn ($sub) => $sub->whereIn('students.id', $studentIds)))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $totals = $this->buildTotals($attendances, $studentIds);

        // Group by month-year for the list
        $monthlyList = $attendances
            ->groupBy(fn ($a) => sprintf('%04d-%02d', $a->year, $a->month))
            ->map(function ($items, $period) use ($students) {
                [$year, $month] = explode('-', $period);
                $total = 0;
                $status = 'unpaid';
                $hasProof = false;
                $proofStatus = 'none';

                foreach ($items as $attendance) {
                    foreach ($students as $student) {
                        $s = $attendance->students->firstWhere('id', $student->id);
                        $present = (int) ($s?->pivot?->total_present ?? 0);
                        $rate = $attendance->enrollment?->parent_rate ?? 0;
                        $total += $present * $rate;
                    }

                    // Aggregate status: if any is paid, show paid; if any has proof, show pending
                    if ($attendance->parent_payment_status === 'paid') {
                        $status = 'paid';
                    } elseif ($attendance->payment_proof_status === 'pending') {
                        $status = 'pending';
                        $hasProof = true;
                        $proofStatus = 'pending';
                    } elseif ($attendance->payment_proof) {
                        $hasProof = true;
                        $proofStatus = $attendance->payment_proof_status;
                    } elseif ($status !== 'paid' && $status !== 'pending') {
                        $status = $attendance->parent_payment_status ?? 'unpaid';
                    }
                }

                // Check if invoice PDF exists for any student
                $hasInvoice = false;
                $invoiceUrl = null;
                foreach ($students as $student) {
                    $invoicePath = sprintf('invoice/%s/%s_%04d-%02d.pdf', $student->id, str_replace(' ', '_', $student->name ?? ''), (int) $year, (int) $month);
                    if (Storage::disk('public')->exists($invoicePath)) {
                        $hasInvoice = true;
                        $invoiceUrl = asset('storage/' . $invoicePath);
                        break;
                    }
                }

                return [
                    'period' => sprintf('%s %s', $this->monthName((int) $month), $year),
                    'year' => (int) $year,
                    'month' => (int) $month,
                    'total' => $total,
                    'status' => $status,
                    'has_proof' => $hasProof,
                    'proof_status' => $proofStatus,
                    'attendance_ids' => $items->pluck('id')->toArray(),
                    'has_invoice' => $hasInvoice,
                    'invoice_url' => $invoiceUrl,
                ];
            })
            ->values();

        return view('parent.billing.index', [
            'students' => $students,
            'totals' => $totals,
            'monthlyList' => $monthlyList,
        ]);
    }

    public function uploadProof(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $parent = $request->user()?->parent;
        $studentIds = $parent?->students->pluck('id')->toArray() ?? [];

        // Verify this parent has at least one student associated with this attendance
        $hasAccess = $attendance->students()->whereIn('students.id', $studentIds)->exists();
        if (! $hasAccess) {
            abort(403, 'Anda tidak berhak mengupload bukti untuk tagihan ini.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $path = $validated['payment_proof']->store('payment-proofs', 'public');

        $attendance->update([
            'payment_proof' => $path,
            'payment_proof_status' => 'pending',
        ]);

        return back()->with('status', 'Bukti pembayaran berhasil diupload, menunggu konfirmasi admin.');
    }

    public function downloadInvoice(Request $request, int $year, int $month): RedirectResponse
    {
        $parent = $request->user()?->parent;
        $studentIds = $parent?->students->pluck('id')->toArray() ?? [];

        if (empty($studentIds)) {
            abort(404);
        }

        $invoiceService = app(InvoiceService::class);

        // Get attendances for all students for this period
        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->whereHas('students', fn ($sub) => $sub->whereIn('students.id', $studentIds))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        if ($attendances->isEmpty()) {
            abort(404, 'Tidak ada data tagihan untuk periode ini.');
        }

        // Generate invoice for the first student (or aggregate)
        $firstStudent = $parent?->students->first();
        $filename = $invoiceService->generateStudentInvoice($firstStudent, $month, $year, $attendances);

        return redirect(asset('storage/' . $filename));
    }

    private function buildTotals($attendances, array $studentIds): array
    {
        $rows = $attendances->map(function (MonthlyAttendance $attendance) use ($studentIds) {
            $total = 0;
            foreach ($studentIds as $studentId) {
                $student = $attendance->students->firstWhere('id', $studentId);
                $present = (int) ($student?->pivot?->total_present ?? 0);
                $rate = $attendance->enrollment?->parent_rate ?? 0;
                $total += $present * $rate;
            }

            return [
                'status' => $attendance->parent_payment_status ?? 'unknown',
                'total' => $total,
            ];
        });

        return [
            'paid' => (int) $rows->where('status', 'paid')->sum('total'),
            'unpaid' => (int) $rows->where('status', 'unpaid')->sum('total'),
            'grand' => (int) $rows->sum('total'),
        ];
    }

    private function monthName(int $month): string
    {
        $names = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $names[$month] ?? 'Bulan';
    }
}