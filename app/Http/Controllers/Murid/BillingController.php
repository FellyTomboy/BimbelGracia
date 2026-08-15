<?php

declare(strict_types=1);

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Services\CalculationService;
use App\Services\Pdf\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private CalculationService $calculationService
    ) {}
    public function index(Request $request): View
    {
        $parent = $request->user()?->parent;
        $student = $parent?->students()->first();

        // Get all attendances for this student across all time
        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when($student, fn ($query) => $query->whereHas('students', fn ($sub) => $sub->where('students.id', $student->id)))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where(fn ($query) => $query->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $totals = $this->buildTotals($attendances, $student?->id);

        // Group by month-year for the list
        $monthlyList = $attendances
            ->groupBy(fn ($a) => sprintf('%04d-%02d', $a->year, $a->month))
            ->map(function ($items, $period) use ($student, $parent) {
                [$year, $month] = explode('-', $period);
                $total = 0;
                $status = 'unpaid';
                $hasProof = false;
                $proofStatus = 'none';

                // Calculate total using CalculationService
                if ($student) {
                    $studentAttendances = $items->filter(fn ($a) => $a->students->contains($student->id));
                    if ($studentAttendances->isNotEmpty()) {
                        $result = $this->calculationService->calculateStudentBilling($student, (int) $month, (int) $year, $studentAttendances);
                        $total = $result['grand_total'];
                    }
                }

                foreach ($items as $attendance) {
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
                $parentId = $parent?->id ?? 'unknown';
                $invoicePath = sprintf('pdf/invoice/parent_%s/%02d-%04d.pdf', $parentId, (int) $month, (int) $year);
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
        $parent = $request->user()?->parent;
        $student = $parent?->students()->first();

        // Verify this student is associated with this attendance
        if (! $student || ! $attendance->students->contains($student->id)) {
            abort(403, 'Anda tidak berhak mengupload bukti untuk tagihan ini.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $parentId = $parent?->id ?? 'unknown';
        $file = $validated['payment_proof'];
        $extension = $file->getClientOriginalExtension();
        $period = sprintf('%02d-%04d', $attendance->month, $attendance->year);
        $path = sprintf('photo/transfer-proof/parent_%s/%s.%s', $parentId, $period, $extension);
        $file->storeAs(dirname($path), basename($path), 'public');

        $attendance->update([
            'payment_proof' => $path,
            'payment_proof_status' => 'pending',
        ]);

        return back()->with('status', 'Bukti pembayaran berhasil diupload, menunggu konfirmasi admin.');
    }

    public function downloadInvoice(Request $request, int $year, int $month): RedirectResponse
    {
        $parent = $request->user()?->parent;
        $student = $parent?->students()->first();

        if (!$student) {
            abort(404);
        }

        $invoiceService = app(InvoiceService::class);

        // Get attendances for this period
        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->whereHas('students', fn ($sub) => $sub->where('students.id', $student->id))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where(fn ($query) => $query->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
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
            if (!$student) {
                return ['status' => $attendance->parent_payment_status ?? 'unknown', 'total' => 0];
            }
            $result = $this->calculationService->calculateStudentBilling($student, $attendance->month, $attendance->year, collect([$attendance]));
            return [
                'status' => $attendance->parent_payment_status ?? 'unknown',
                'total' => $result['grand_total'],
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