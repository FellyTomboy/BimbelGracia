<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

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
        $students = $parent?->students ?? collect();
        $studentIds = $students->pluck('id')->toArray();

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->when(!empty($studentIds), fn ($query) => $query->whereHas('students', fn ($sub) => $sub->whereIn('students.id', $studentIds)))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $totals = $this->buildTotals($attendances, $students);

        // Group by month-year for the list
        $monthlyList = $attendances
            ->groupBy(fn ($a) => sprintf('%04d-%02d', $a->year, $a->month))
            ->map(function ($items, $period) use ($students, $parent) {
                [$year, $month] = explode('-', $period);
                $total = 0;
                $status = 'unpaid';
                $hasProof = false;
                $proofStatus = 'none';

                // Calculate total using CalculationService per student
                foreach ($students as $student) {
                    $studentAttendances = $items->filter(fn ($a) => $a->students->contains($student->id));
                    if ($studentAttendances->isNotEmpty()) {
                        $result = $this->calculationService->calculateStudentBilling($student, (int) $month, (int) $year, $studentAttendances);
                        $total += $result['grand_total'];
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

                // Check if parent invoice PDF exists
                $parentName = $parent?->name ?? 'unknown';
                $parentSlug = str_replace(' ', '_', strtolower($parentName));
                $parentInvoicePath = sprintf('pdf/invoice/%s/%02d-%04d.pdf', $parentSlug, (int) $month, (int) $year);
                $hasInvoice = Storage::disk('public')->exists($parentInvoicePath);
                $invoiceUrl = $hasInvoice ? asset('storage/' . $parentInvoicePath) : null;

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

        $hasAccess = $attendance->students()->whereIn('students.id', $studentIds)->exists();
        if (! $hasAccess) {
            abort(403, 'Anda tidak berhak mengupload bukti untuk tagihan ini.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $parentName = $parent?->name ?? 'unknown';
        $parentSlug = str_replace(' ', '_', strtolower($parentName));
        $file = $validated['payment_proof'];
        $extension = $file->getClientOriginalExtension();
        $period = sprintf('%02d-%04d', $attendance->month, $attendance->year);
        $path = sprintf('photo/transfer-proof/%s/%s.%s', $parentSlug, $period, $extension);
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
        $studentIds = $parent?->students->pluck('id')->toArray() ?? [];

        if (empty($studentIds)) {
            abort(404);
        }

        $invoiceService = app(InvoiceService::class);

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->whereHas('students', fn ($sub) => $sub->whereIn('students.id', $studentIds))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        if ($attendances->isEmpty()) {
            abort(404, 'Tidak ada data tagihan untuk periode ini.');
        }

        $students = $parent?->students ?? collect();
        $filename = $invoiceService->generateParentInvoice($students, $month, $year, $attendances);

        return redirect(asset('storage/' . $filename));
    }

    private function buildTotals($attendances, $students): array
    {
        $paid = 0;
        $unpaid = 0;

        foreach ($attendances->groupBy(fn ($a) => sprintf('%04d-%02d', $a->year, $a->month)) as $period => $items) {
            [$year, $month] = explode('-', $period);
            $periodTotal = 0;

            foreach ($students as $student) {
                $studentAttendances = $items->filter(fn ($a) => $a->students->contains($student->id));
                if ($studentAttendances->isNotEmpty()) {
                    $result = $this->calculationService->calculateStudentBilling($student, (int) $month, (int) $year, $studentAttendances);
                    $periodTotal += $result['grand_total'];
                }
            }

            $isPaid = $items->every(fn ($a) => $a->parent_payment_status === 'paid');
            if ($isPaid) {
                $paid += $periodTotal;
            } else {
                $unpaid += $periodTotal;
            }
        }

        return [
            'paid' => $paid,
            'unpaid' => $unpaid,
            'grand' => $paid + $unpaid,
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