<?php

declare(strict_types=1);

namespace App\Http\Controllers\Murid;

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
        $student = Student::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        // Get all attendances for this student across all time
        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when($student, fn ($query) => $query->whereHas('students', fn ($sub) => $sub->where('students.id', $student->id)))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $totals = $this->buildTotals($attendances, $student?->id);

        // Group by month-year for the list
        $monthlyList = $attendances
            ->groupBy(fn ($a) => sprintf('%04d-%02d', $a->year, $a->month))
            ->map(function ($items, $period) use ($student) {
                [$year, $month] = explode('-', $period);
                $total = 0;
                $status = 'unpaid';
                $hasProof = false;
                $proofStatus = 'none';

                foreach ($items as $attendance) {
                    $s = $attendance->students->firstWhere('id', $student?->id ?? 0);
                    $present = (int) ($s?->pivot?->total_present ?? 0);
                    $rate = $attendance->enrollment?->parent_rate ?? 0;
                    $total += $present * $rate;

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

                // Check if invoice PDF exists
                $invoicePath = sprintf('invoice/%s/%s_%04d-%02d.pdf', $student?->id, str_replace(' ', '_', $student?->name ?? ''), (int) $year, (int) $month);
                $hasInvoice = $student && Storage::disk('public')->exists($invoicePath);

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
                    'invoice_url' => $hasInvoice ? asset('storage/' . $invoicePath) : null,
                ];
            })
            ->values();

        return view('murid.billing.index', [
            'student' => $student,
            'totals' => $totals,
            'monthlyList' => $monthlyList,
        ]);
    }

    public function uploadProof(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $student = Student::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        // Verify this student is associated with this attendance
        if (! $student || ! $attendance->students->contains($student->id)) {
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
        $student = Student::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        if (!$student) {
            abort(404);
        }

        $invoiceService = app(InvoiceService::class);

        // Get attendances for this period
        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->whereHas('students', fn ($sub) => $sub->where('students.id', $student->id))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        if ($attendances->isEmpty()) {
            abort(404, 'Tidak ada data tagihan untuk periode ini.');
        }

        $filename = $invoiceService->generateStudentInvoice($student, $month, $year, $attendances);

        return redirect(asset('storage/' . $filename));
    }

    private function buildTotals($attendances, ?int $studentId): array
    {
        $rows = $attendances->map(function (MonthlyAttendance $attendance) use ($studentId) {
            $student = $attendance->students->firstWhere('id', $studentId);
            $present = (int) ($student?->pivot?->total_present ?? 0);
            $rate = $attendance->enrollment?->parent_rate ?? 0;
            $total = $present * $rate;

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