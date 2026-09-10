<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Helpers\StudentGrade;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\PaymentProof;
use App\Models\Student;
use App\Services\CalculationService;
use App\Services\Pdf\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->where(fn ($query) => $query->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
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
                $proof = null;

                // Calculate total using CalculationService per student
                foreach ($students as $student) {
                    $studentAttendances = $items->filter(fn ($a) => $a->students->contains($student->id));
                    if ($studentAttendances->isNotEmpty()) {
                        $result = $this->calculationService->calculateStudentBilling($student, (int) $month, (int) $year, $studentAttendances);
                        $total += $result['grand_total'];
                    }
                }

                $proof = PaymentProof::where('parent_id', $parent?->id)
                    ->where('month', (int) $month)
                    ->where('year', (int) $year)
                    ->first();

                if ($proof) {
                    $status = $proof->status === 'approved' ? 'paid' : ($proof->status === 'pending' ? 'pending' : 'unpaid');
                } else {
                    // Fallback: check old per-attendance proof for backwards compat
                    foreach ($items as $attendance) {
                        if ($attendance->parent_payment_status === 'paid') {
                            $status = 'paid';
                            break;
                        }
                    }
                }

                // Check if parent invoice PDF exists from database
                $invoice = Invoice::where('parent_id', $parent?->id)
                    ->where('month', (int) $month)
                    ->where('year', (int) $year)
                    ->first();
                $hasInvoice = $invoice !== null;
                $invoiceUrl = $hasInvoice
                    ? route('pdf.parent', [$parent?->id, $invoice->filename])
                    : null;

                return [
                    'period' => sprintf('%s %s', $this->monthName((int) $month), $year),
                    'year' => (int) $year,
                    'month' => (int) $month,
                    'total' => $total,
                    'status' => $status,
                    'has_proof' => $proof !== null,
                    'proof' => $proof,
                    'proof_status' => $proof?->status ?? 'none',
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
            'parent' => $parent,
        ]);
    }

    public function uploadProof(Request $request, int $parentId, int $year, int $month): RedirectResponse
    {
        $parent = $request->user()?->parent;

        if (! $parent || $parent->id !== $parentId) {
            abort(403, 'Anda tidak berhak mengupload bukti untuk tagihan ini.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $file = $validated['payment_proof'];
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf('%s_%02d_%04d_%s.%s', $parentId, $month, $year, time(), $extension);
        $path = sprintf('photo/transfer-proof/parent_%s/%s', $parentId, $filename);
        $file->storeAs(dirname($path), basename($path), 'public');

        PaymentProof::updateOrCreate(
            ['parent_id' => $parentId, 'month' => $month, 'year' => $year],
            ['proof_path' => $path, 'status' => 'pending']
        );

        return back()->withInput()->with('status', 'Bukti pembayaran berhasil diupload, menunggu konfirmasi admin.');
    }

    public function completeData(Request $request): View
    {
        $parent = $request->user()?->parent;
        abort_unless($parent, 403);

        $students = $parent->students()->orderBy('nickname')->get();

        return view('parent.complete-data', [
            'parent' => $parent,
            'students' => $students,
            'redirect_to' => $request->query('redirect_to', route('parent.billing.index')),
        ]);
    }

    public function submitCompleteData(Request $request): RedirectResponse
    {
        $parent = $request->user()?->parent;
        abort_unless($parent, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'students' => ['required', 'array'],
            'students.*.id' => ['required', 'integer', 'exists:students,id'],
            'students.*.nickname' => ['required', 'string', 'max:255'],
            'students.*.full_name' => ['required', 'string', 'max:255'],
            'students.*.sekolah' => ['required', 'string', 'max:255'],
            'students.*.kelas' => ['required', 'string', Rule::in(StudentGrade::LEVELS)],
        ]);

        $parentName = trim($validated['name']);
        $parentAddress = trim($validated['address']);

        $parent->update([
            'name' => $parentName,
            'address' => $parentAddress,
        ]);

        if ($parent->user) {
            $parent->user->update([
                'name' => $parentName,
            ]);
        }

        foreach ($validated['students'] ?? [] as $studentPayload) {
            $student = $parent->students()->find($studentPayload['id'] ?? null);
            if ($student) {
                $student->update([
                    'nickname' => trim($studentPayload['nickname'] ?? ''),
                    'full_name' => trim($studentPayload['full_name'] ?? ''),
                    'sekolah' => trim($studentPayload['sekolah'] ?? ''),
                    'kelas' => $studentPayload['kelas'] ?? null,
                ]);
            }
        }

        $redirectTo = $request->input('redirect_to', route('parent.billing.index'));

        return redirect()->to($redirectTo)
            ->with('status', 'Data orang tua dan murid berhasil diperbarui.');
    }

    public function completeDataPublic(Request $request, int $parent): View
    {
        $parentModel = ParentModel::findOrFail($parent);

        return view('parent.complete-data-public', [
            'parent' => $parentModel,
            'students' => $parentModel->students()->orderBy('nickname')->get(),
            'redirect_to' => $request->query('redirect_to', route('parent.billing.index')),
        ]);
    }

    public function submitCompleteDataPublic(Request $request, int $parent): RedirectResponse
    {
        $parentModel = ParentModel::findOrFail($parent);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'students' => ['required', 'array'],
            'students.*.id' => ['required', 'integer', 'exists:students,id'],
            'students.*.nickname' => ['required', 'string', 'max:255'],
            'students.*.full_name' => ['required', 'string', 'max:255'],
            'students.*.sekolah' => ['required', 'string', 'max:255'],
            'students.*.kelas' => ['required', 'string', Rule::in(StudentGrade::LEVELS)],
        ]);

        $parentName = trim($validated['name']);
        $parentModel->update([
            'name' => $parentName,
            'address' => trim($validated['address']),
        ]);

        if ($parentModel->user) {
            $parentModel->user->update(['name' => $parentName]);
        }

        foreach ($validated['students'] ?? [] as $studentPayload) {
            $student = $parentModel->students()->find($studentPayload['id'] ?? null);
            if ($student) {
                $student->update([
                    'nickname' => trim($studentPayload['nickname'] ?? ''),
                    'full_name' => trim($studentPayload['full_name'] ?? ''),
                    'sekolah' => trim($studentPayload['sekolah'] ?? ''),
                    'kelas' => $studentPayload['kelas'] ?? null,
                ]);
            }
        }

        $redirectTo = $request->input('redirect_to', route('parent.billing.index'));

        return redirect()->to($redirectTo)
            ->with('status', 'Data orang tua dan murid berhasil diperbarui.');
    }

    public function downloadInvoice(Request $request, int $year, int $month): RedirectResponse
    {
        $parent = $request->user()?->parent;
        $studentIds = $parent?->students->pluck('id')->toArray() ?? [];

        if (empty($studentIds)) {
            abort(404);
        }

        $missingDataRedirect = $this->redirectIfInvoiceDataMissing($parent, route('parent.billing.download-invoice', ['year' => $year, 'month' => $month]));
        if ($missingDataRedirect) {
            return $missingDataRedirect;
        }

        $invoiceService = app(InvoiceService::class);

        $attendances = MonthlyAttendance::with(['enrollment.teacher', 'enrollment.program', 'students'])
            ->whereHas('students', fn ($sub) => $sub->whereIn('students.id', $studentIds))
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where(fn ($query) => $query->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        if ($attendances->isEmpty()) {
            abort(404, 'Tidak ada data tagihan untuk periode ini.');
        }

        $students = $parent?->students ?? collect();
        $invoice = Invoice::firstOrCreate(
            ['parent_id' => $parent->id, 'month' => $month, 'year' => $year],
            ['filename' => '']
        );
        $result = $invoiceService->generateParentInvoice(
            $students, $month, $year, $attendances,
            $invoice->filename ?: null
        );
        if (!$invoice->filename) {
            $invoice->filename = basename($result['storage_path']);
            $invoice->regenerated_at = now();
            $invoice->save();
        }
        return redirect(route('pdf.parent', [$parent->id, $invoice->filename]));
    }

    private function redirectIfInvoiceDataMissing($parent, string $redirectRoute): ?RedirectResponse
    {
        if (! $parent) {
            return null;
        }

        $missingParentData = blank($parent->name) || blank($parent->address);
        $missingStudentData = $parent->students()->where(fn ($query) => $query
            ->where(fn ($q) => $q->whereNull('nickname')->orWhereRaw('TRIM(COALESCE(nickname, "")) = ""'))
            ->orWhere(fn ($q) => $q->whereNull('full_name')->orWhereRaw('TRIM(COALESCE(full_name, "")) = ""'))
            ->orWhere(fn ($q) => $q->whereNull('sekolah')->orWhereRaw('TRIM(COALESCE(sekolah, "")) = ""'))
            ->orWhere(fn ($q) => $q->whereNull('kelas')->orWhereRaw('TRIM(COALESCE(kelas, "")) = ""'))
        )->exists();

        if ($missingParentData || $missingStudentData) {
            return redirect()->route('parent.billing.complete-data', ['redirect_to' => $redirectRoute]);
        }

        return null;
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
            } elseif ($items->contains(fn ($a) => $a->payment_proof_status === 'pending')) {
                // Proof uploaded but not yet approved — not unpaid, not paid
                // Skip this period from both paid and unpaid totals
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