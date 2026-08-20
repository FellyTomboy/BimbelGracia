<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\EnrollmentStudentDiscount;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceFineService;
use App\Services\Pdf\InvoiceService;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function __construct(private AttendanceFineService $fineService) {}

    public function ortu(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $rows = $this->attendanceRows($attendances);
        $discounts = $this->enrollmentDiscountsByPeriod($month, $year);

        $privatSummaries = $rows
            ->groupBy(function (array $row) {
                $parent = $row['student']->parent;
                return $parent?->id ?? 'unknown';
            })
            ->map(function (Collection $items, string $parentId) use ($month, $year, $discounts) {
                $firstRow = $items->first();
                $parent = $firstRow['student']->parent;
                $contact = $parent?->user?->phone ?? 'unknown';
                $parentName = $parent?->name ?? 'Unknown';

                $students = $items
                    ->groupBy(fn (array $row) => $row['student']->id)
                    ->map(function (Collection $studentItems) use ($discounts) {
                        $student = $studentItems->first()['student'];
                        $studentId = $student?->id;
                        $lines = $studentItems
                            ->groupBy(fn (array $row) => $row['enrollment']->id)
                            ->map(function (Collection $enrollmentItems) use ($discounts, $studentId) {
                                $row = $enrollmentItems->first();
                                $enrollment = $row['enrollment'];
                                $teacherName = $row['teacher']?->name ?? '-';
                                $programName = $row['program']?->name ?? '-';
                                $enrollmentId = $row['enrollment']?->id;
                                $type = $enrollment?->isKelas() ? 'kelas' : 'privat';

                                if ($type === 'kelas') {
                                    // For kelas: use 50% package rule
                                    $agreedSessions = (int) ($enrollment->agreed_sessions_per_month ?? 4);
                                    $studentTotalPresent = $enrollmentItems->sum('total_present');
                                    $attendancePercent = $agreedSessions > 0 ? ($studentTotalPresent / $agreedSessions) * 100 : 0;
                                    $rate = $attendancePercent <= 50
                                        ? (int) round((float) ($enrollment->parent_rate ?? 0) * 0.5)
                                        : (int) ($enrollment->parent_rate ?? 0);
                                    $count = 1;
                                    $total = $rate;
                                    $label = sprintf('Paket Kelas %s', $programName);
                                } else {
                                    // For privat: per-session pricing
                                    // Each session may have DIFFERENT rate (multi-student tiers),
                                    // so we must sum individual session costs, not use a single rate
                                    $rate = $row['parent_rate']; // display rate (base snapshot)
                                    $count = $enrollmentItems->sum('total_present');
                                    $baseTotal = $enrollmentItems->sum(function (array $row) {
                                        return (int) ($row['parent_rate'] ?? 0) * (int) ($row['total_present'] ?? 0);
                                    });

                                    // Penalty: Rp 5,000 per session student actually attended, applied before discount
                                    $totalSessionsThisEnrollment = $enrollmentItems->count();
                                    $studentTotalPresentForPenalty = $count; // same as count for this student
                                    $hasPenalty = $enrollment && $this->fineService->isAttendancePenaltyEnabled()
                                        && $enrollment->hasAttendancePenalty($totalSessionsThisEnrollment, $studentTotalPresentForPenalty);
                                    $penalty = $hasPenalty ? $studentTotalPresentForPenalty * 5000 : 0;
                                    $adjustedRate = $hasPenalty ? $rate + 5000 : $rate;
                                    $inflatedSubtotal = $adjustedRate * $count;
                                    $total = $inflatedSubtotal; // gross including penalty

                                    $label = sprintf('%s (%s)', $teacherName, $programName);

                                    $discountModel = $discounts[$this->discountKey($enrollmentId, $studentId)] ?? null;
                                    $discount = $this->resolveDiscount($total, $discountModel?->discount_type, $discountModel?->discount_value);

                                    return [
                                        'label' => $label,
                                        'count' => $count,
                                        'rate' => $rate,
                                        'total' => $total,
                                        'penalty' => $penalty,
                                        'total_after' => $discount['total'],
                                        'discount' => $discount,
                                        'enrollment_id' => $enrollmentId,
                                        'student_id' => $studentId,
                                        'type' => $type,
                                    ];
                                }

                                $discountModel = $discounts[$this->discountKey($enrollmentId, $studentId)] ?? null;
                                $discount = $this->resolveDiscount($total, $discountModel?->discount_type, $discountModel?->discount_value);

                                return [
                                    'label' => $label,
                                    'count' => $count,
                                    'rate' => $rate,
                                    'total' => $total,
                                    'total_after' => $discount['total'],
                                    'discount' => $discount,
                                    'enrollment_id' => $enrollmentId,
                                    'student_id' => $studentId,
                                    'type' => $type,
                                ];
                            })
                            ->values();

                        return [
                            'student' => $student,
                            'lines' => $lines,
                            'total' => $lines->sum('total_after'),
                            'total_before' => $lines->sum('total'),
                        ];
                    })
                    ->values();

                $grandTotal = $students->sum('total');
                $message = $this->buildPrivateParentMessage($students, $month, $year, $grandTotal);

                return [
                    'parent_id' => $parentId,
                    'parent_name' => $parentName,
                    'contact' => $contact,
                    'students' => $students,
                    'total' => $grandTotal,
                    'message' => $message,
                ];
            })
            ->values();

        return view('admin.analysis.ortu', [
            'month' => $month,
            'year' => $year,
            'privatSummaries' => $privatSummaries,
        ]);
    }

    public function guru(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $rows = $this->attendanceRows($attendances);

        $grouped = $rows
            ->groupBy(fn (array $row) => $row['teacher']?->id)
            ->map(function (Collection $items) use ($month, $year) {
                $teacher = $items->first()['teacher'];
                $lines = $items
                    ->groupBy(fn (array $row) => $row['enrollment']->id)
                    ->map(function (Collection $enrollmentItems) {
                        $row = $enrollmentItems->first();
                        $enrollment = $row['enrollment'];
                        $studentName = $enrollmentItems->pluck('student')->filter()->unique('id')->map->display_name->implode(', ');
                        $programName = $row['program']?->name ?? '-';
                        $rate = $row['teacher_rate'];
                        $totalCount = $enrollmentItems->sum('total_present');
                        $lateCount = $enrollmentItems->where('status_validation', 'terlambat')->count();
                        $grossTotal = $totalCount * $rate;
                        $penalty = $this->fineService->isLatePenaltyEnabled()
                            ? (int) ($lateCount * $rate * 0.1)
                            : 0;
                        $type = $enrollment?->isKelas() ? 'kelas' : 'privat';

                        return [
                            'label' => sprintf('%s (%s)', $studentName ?: '-', $programName),
                            'count' => $totalCount,
                            'rate' => $rate,
                            'total' => $grossTotal,
                            'penalty' => (int) $penalty,
                            'late_count' => $lateCount,
                            'type' => $type,
                        ];
                    })
                    ->values();

                $grandTotal = (int) $lines->sum('total');
                $latePenalty = (int) $lines->sum('penalty');
                $lateCountTotal = (int) $lines->sum('late_count');
                $finalTotal = $grandTotal - $latePenalty;
                $message = $this->buildTeacherMessage($teacher, $lines, $month, $year, $grandTotal, $latePenalty, $lateCountTotal, $finalTotal);

                return [
                    'teacher' => $teacher,
                    'lines' => $lines,
                    'total' => $finalTotal,
                    'gross' => $grandTotal,
                    'late_penalty' => $latePenalty,
                    'late_count' => $lateCountTotal,
                    'message' => $message,
                ];
            })
            ->values();

        return view('admin.analysis.guru', [
            'month' => $month,
            'year' => $year,
            'summaries' => $grouped,
        ]);
    }

    public function paymentsOrtu(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $rows = $this->attendanceRows($attendances);

        $discounts = $this->enrollmentDiscountsByPeriod($month, $year);

        $summaries = $rows
            ->groupBy(function (array $row) {
                $parent = $row['student']->parent;
                return $parent?->id ?? 'unknown';
            })
            ->map(function (Collection $items) use ($month, $year) {
                $firstRow = $items->first();
                $parent = $firstRow['student']->parent;
                $parentName = $parent?->name ?? 'Unknown';

                $students = $items
                    ->groupBy(fn (array $row) => $row['student']->id)
                    ->map(function (Collection $studentItems) use ($month, $year) {
                        $student = $studentItems->first()['student'];
                        $studentId = $student?->id;
                        $lines = $studentItems
                            ->groupBy(fn (array $row) => $row['enrollment']->id)
                            ->map(function (Collection $enrollmentItems) use ($month, $year, $studentId) {
                                $row = $enrollmentItems->first();
                                $enrollment = $row['enrollment'];
                                $enrollmentId = $enrollment?->id;
                                $teacherName = $row['teacher']?->name ?? '-';
                                $programName = $row['program']?->name ?? '-';
                                $type = $enrollment?->isKelas() ? 'kelas' : 'privat';

                                if ($type === 'kelas') {
                                    $agreedSessions = (int) ($enrollment->agreed_sessions_per_month ?? 4);
                                    $studentTotalPresent = $enrollmentItems->sum('total_present');
                                    $attendancePercent = $agreedSessions > 0 ? ($studentTotalPresent / $agreedSessions) * 100 : 0;
                                    $rate = $attendancePercent <= 50
                                        ? (int) round((float) ($enrollment->parent_rate ?? 0) * 0.5)
                                        : (int) ($enrollment->parent_rate ?? 0);
                                    $count = 1;
                                    $total = $rate;
                                    $label = sprintf('Paket Kelas %s', $programName);
                                } else {
                                    $rate = $row['parent_rate'];
                                    $count = $enrollmentItems->sum('total_present');
                                    $baseTotal = $enrollmentItems->sum(function (array $r) {
                                        return (int) ($r['parent_rate'] ?? 0) * (int) ($r['total_present'] ?? 0);
                                    });

                                    // Penalty: Rp 5,000 per session student attended, applied before discount
                                    $totalSessionsThisEnrollment = $enrollmentItems->count();
                                    $hasPenalty = $enrollment && $this->fineService->isAttendancePenaltyEnabled()
                                        && $enrollment->hasAttendancePenalty($totalSessionsThisEnrollment, $count);
                                    $penalty = $hasPenalty ? $count * 5000 : 0;
                                    $adjustedRate = $hasPenalty ? $rate + 5000 : $rate;
                                    $total = $adjustedRate * $count; // inflated subtotal including penalty

                                    $label = sprintf('%s (%s)', $teacherName, $programName);
                                }

                                $discountModel = $discounts[$this->discountKey($enrollmentId, $studentId)] ?? null;
                                $discount = $this->resolveDiscount($total, $discountModel?->discount_type, $discountModel?->discount_value);

                                $att = $row['attendance'];

                                return [
                                    'label' => $label,
                                    'count' => $count,
                                    'rate' => $rate,
                                    'total' => $total,
                                    'total_after' => $discount['total'],
                                    'discount_type' => $discount['type'],
                                    'discount_amount' => $discount['amount'],
                                    'discount_label' => $discount['label'],
                                    'payment_status' => $att->parent_payment_status,
                                    'attendance_id' => $att->id,
                                    'proof_url' => $att->payment_proof,
                                    'proof_status' => $att->payment_proof_status ?? 'none',
                                ];
                            })
                            ->values();

                        return [
                            'student' => $student,
                            'lines' => $lines,
                            'total' => $lines->sum('total_after'),
                            'total_before' => $lines->sum('total'),
                        ];
                    })
                    ->values();

                return [
                    'parent_name' => $parentName,
                    'students' => $students,
                    'total' => $students->sum('total'),
                    'total_before' => $students->sum('total_before'),
                ];
            })
            ->values();

        return view('admin.payments.ortu', [
            'month' => $month,
            'year' => $year,
            'summaries' => $summaries,
        ]);
    }

    public function paymentsGuru(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $rows = $this->attendanceRows($attendances);

        $summaries = $rows
            ->groupBy(fn (array $row) => $row['teacher']?->id)
            ->map(function (Collection $items) {
                $teacher = $items->first()['teacher'];
                $lines = $items
                    ->groupBy(fn (array $row) => $row['enrollment']->id)
                    ->map(function (Collection $enrollmentItems) {
                        $row = $enrollmentItems->first();
                        $studentName = $enrollmentItems->pluck('student')->filter()->unique('id')->map->display_name->implode(', ');
                        $programName = $row['program']?->name ?? '-';
                        $rate = $row['teacher_rate'];
                        $totalCount = $enrollmentItems->sum('total_present');
                        $lateCount = $enrollmentItems->where('status_validation', 'terlambat')->count();
                        $grossTotal = $totalCount * $rate;
                        $penalty = $this->fineService->isLatePenaltyEnabled()
                            ? (int) ($lateCount * $rate * 0.1)
                            : 0;

                        return [
                            'label' => sprintf('%s (%s)', $studentName ?: '-', $programName),
                            'count' => $totalCount,
                            'rate' => $rate,
                            'total' => $grossTotal,
                            'penalty' => (int) $penalty,
                            'late_count' => $lateCount,
                            'payment_status' => $row['attendance']->teacher_payment_status,
                            'attendance_id' => $row['attendance']->id,
                        ];
                    })
                    ->values();

                $grandTotal = (int) $lines->sum('total');
                $latePenalty = (int) $lines->sum('penalty');

                return [
                    'teacher' => $teacher,
                    'lines' => $lines,
                    'total' => $grandTotal,
                    'penalty' => $latePenalty,
                ];
            })
            ->values();

        return view('admin.payments.guru', [
            'month' => $month,
            'year' => $year,
            'summaries' => $summaries,
        ]);
    }

    public function updateParentPayment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'parent_payment_status' => ['required', 'in:unpaid,paid'],
        ]);

        $attendance->update($validated);

        return back()->with('status', 'Status pembayaran ortu diperbarui.');
    }

    public function updateTeacherPayment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_payment_status' => ['required', 'in:unpaid,paid,held'],
        ]);

        $attendance->update($validated);

        return back()->with('status', 'Status gaji guru diperbarui.');
    }

    public function confirmPaymentProof(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        if ($validated['action'] === 'approve') {
            $attendance->update([
                'payment_proof_status' => 'approved',
                'parent_payment_status' => 'paid',
            ]);
            return back()->with('status', 'Bukti pembayaran disetujui. Status berubah menjadi LUNAS.');
        }

        $attendance->update([
            'payment_proof_status' => 'rejected',
        ]);

        return back()->with('status', 'Bukti pembayaran ditolak. Silakan minta upload ulang.');
    }

    public function updateEnrollmentDiscount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'discount_type' => ['required', 'in:none,percent,final,amount'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = $validated['discount_type'];
        $value = $validated['discount_value'];

        if ($type === 'none' || $value === null || $value === 0) {
            EnrollmentStudentDiscount::query()
                ->where('enrollment_id', $validated['enrollment_id'])
                ->where('student_id', $validated['student_id'])
                ->where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->delete();

            return back()->with('status', 'Diskon privat dihapus.');
        }

        EnrollmentStudentDiscount::updateOrCreate(
            [
                'enrollment_id' => $validated['enrollment_id'],
                'student_id' => $validated['student_id'],
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'discount_type' => $type,
                'discount_value' => $value,
            ]
        );

        return back()->with('status', 'Diskon privat diperbarui.');
    }

    public function generateInvoice(Request $request, Student $student, int $month, int $year): RedirectResponse
    {
        $parent = $student->parent;
        if (! $parent) {
            return back()->with('status', 'Murid ini tidak memiliki orang tua.');
        }

        $missingParentData = blank($parent->name) || blank($parent->address);
        $missingStudentData = $parent->students()->where(fn ($query) => $query->whereNull('full_name')->orWhereRaw('TRIM(COALESCE(full_name, "")) = ""'))->exists();

        if ($missingParentData || $missingStudentData) {
            return redirect()->route('parent.billing.complete-data', [
                'redirect_to' => route('admin.analysis.generate-invoice', ['student' => $student->id, 'month' => $month, 'year' => $year]),
            ]);
        }

        $students = $parent->students;

        $attendances = $this->baseAttendanceQuery($month, $year)
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $students->pluck('id')))
            ->where(fn ($query) => $query->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
            ->get();

        if ($attendances->isEmpty()) {
            return back()->with('status', 'Tidak ada data absensi untuk murid-murid ini pada periode tersebut.');
        }

        $invoiceService = app(InvoiceService::class);
        $filename = $invoiceService->generateParentInvoice($students, $month, $year, $attendances);

        return redirect(asset('storage/' . $filename));
    }

    public function generateSalary(Request $request, Teacher $teacher, int $month, int $year): RedirectResponse
    {
        $hasMissingTeacherIdentity = blank($teacher->full_name) && blank($teacher->nickname) && blank($teacher->name);
        $hasMissingTeacherProfile = blank($teacher->major) || blank($teacher->subjects) || blank($teacher->address);

        if ($hasMissingTeacherIdentity || $hasMissingTeacherProfile) {
            return redirect()->route('guru.complete-data', [
                'redirect_to' => route('admin.analysis.generate-salary', ['teacher' => $teacher->id, 'month' => $month, 'year' => $year]),
            ]);
        }

        $attendances = $this->baseAttendanceQuery($month, $year)
            ->where(function ($query) use ($teacher) {
                $query->whereHas('enrollment', fn ($q) => $q->where('teacher_id', $teacher->id))
                    ->orWhere('session_teacher_id', $teacher->id);
            })
            ->get();

        if ($attendances->isEmpty()) {
            return back()->with('status', 'Tidak ada data absensi untuk guru ini pada periode tersebut.');
        }

        $invoiceService = app(InvoiceService::class);
        $filename = $invoiceService->generateTeacherSalarySlip($teacher, $month, $year, $attendances);

        return redirect(asset('storage/' . $filename));
    }

    private function baseAttendanceQuery(int $month, int $year)
    {
        return MonthlyAttendance::query()
            ->with(['enrollment.program', 'enrollment.teacher', 'sessionTeacher', 'students'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('enrollment_id');
    }

    private function attendanceRows(Collection $attendances): Collection
    {
        // Pre-compute per-student monthly totals for penalty calculation
        $monthlyStudentTotals = [];
        foreach ($attendances as $attendance) {
            $enrollmentId = $attendance->enrollment_id;
            foreach ($attendance->students as $student) {
                $key = $enrollmentId . '-' . $student->id;
                $monthlyStudentTotals[$key] = ($monthlyStudentTotals[$key] ?? 0) + ((int) ($student->pivot->total_present ?? 0));
            }
        }

        // Pre-compute total sessions per enrollment per month
        $monthlyEnrollmentSessions = [];
        foreach ($attendances as $attendance) {
            $eid = $attendance->enrollment_id;
            $monthlyEnrollmentSessions[$eid] = ($monthlyEnrollmentSessions[$eid] ?? 0) + 1;
        }

        return $attendances->flatMap(function (MonthlyAttendance $attendance) use ($monthlyStudentTotals, $monthlyEnrollmentSessions) {
            $enrollment = $attendance->enrollment;
            $program = $enrollment?->program;
            $teacher = $enrollment?->isKelas()
                ? $attendance->sessionTeacher
                : $enrollment?->teacher;
            $totalSessionsThisMonth = $monthlyEnrollmentSessions[$attendance->enrollment_id] ?? 0;

            return $attendance->students->map(function (Student $student) use ($attendance, $enrollment, $program, $teacher, $totalSessionsThisMonth, $monthlyStudentTotals) {
                $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
                $studentKey = $attendance->enrollment_id . '-' . $student->id;
                $studentTotalPresent = $monthlyStudentTotals[$studentKey] ?? 0;

                // Use base snapshot rate from attendance record (penalty is shown as separate row field)
                $parentRate = (int) ($attendance->parent_rate ?? $enrollment?->parent_rate ?? 0);
                $teacherRate = (int) ($attendance->teacher_rate ?? $enrollment?->teacher_rate ?? 0);

                return [
                    'attendance' => $attendance,
                    'enrollment' => $enrollment,
                    'program' => $program,
                    'teacher' => $teacher,
                    'student' => $student,
                    'total_present' => (int) ($student->pivot?->total_present ?? 0),
                    'parent_rate' => $parentRate,
                    'teacher_rate' => $teacherRate,
                    'status_validation' => $attendance->status_validation,
                    'has_penalty' => $enrollment?->hasAttendancePenalty($totalSessionsThisMonth, $studentTotalPresent) ?? false,
                ];
            });
        });
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
    }

    private function monthName(int $month): string
    {
        $names = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        return $names[$month] ?? 'Bulan';
    }

    private function buildPrivateParentMessage(Collection $students, int $month, int $year, int $grandTotal): string
    {
        $lines = collect([
            'Selamat pagi Bapak/Ibu. Maaf menganggu waktunya.',
            '',
            sprintf('Total les sampai akhir *%s %s* adalah:', $this->monthName($month), $year),
            '',
        ]);

        $index = 1;
        foreach ($students as $studentSummary) {
            $lines->push(sprintf('%d. *%s*', $index, $studentSummary['student']?->display_name ?? 'Murid'));
            foreach ($studentSummary['lines'] as $line) {
                if ($line['type'] === 'kelas') {
                    // Format for kelas (package) - shows detail about attendance
                    $lines->push(
                        sprintf(
                            '   - *[KELAS]* %s: *Rp %s* = *Rp %s*',
                            $line['label'],
                            number_format($line['rate']),
                            number_format($line['total'])
                        )
                    );
                } else {
                    // Format for privat (per-session) - shows session count
                    $lines->push(
                        sprintf(
                            '   - Tentor *%s*: *%d* x *Rp %s* = *Rp %s*',
                            $line['label'],
                            $line['count'],
                            number_format($line['rate']),
                            number_format($line['total'])
                        )
                    );
                }
                $discount = $line['discount'] ?? null;
                if ($discount && $discount['type']) {
                    if ($discount['type'] === 'final') {
                        $lines->push(sprintf('   - Total akhir: *Rp %s*', number_format($discount['total'])));
                    } else {
                        $lines->push(sprintf('   - Diskon *%s*: -*Rp %s*', $discount['label'], number_format($discount['amount'])));
                        $lines->push(sprintf('   - Total setelah diskon: *Rp %s*', number_format($discount['total'])));
                    }
                }
            }
            $lines->push('');
            $index++;
        }

        $lines->push(sprintf('Total pembayaran sebesar: *Rp %s*', number_format($grandTotal)));
        $lines->push('');
        $lines->push('Mohon dicek kembali. Detail rekening terlampir di PDF invoice.');
        $lines->push('');
        $lines->push('Mohon konfirmasi jika sudah transfer.');
        $lines->push('Jika ada kritik/saran untuk tentor/bimbel, atau ingin mengetahui perkembangan siswa, kami terbuka untuk berdiskusi lewat WhatsApp.');
        $lines->push('Terima kasih atas perhatiannya.');

        return $lines->implode("\n");
    }

    private function buildTeacherMessage(?Teacher $teacher, Collection $lines, int $month, int $year, int $grandTotal, int $latePenalty = 0, int $lateCountTotal = 0, int $finalTotal = 0): string
    {
        $linesText = $lines->values()->map(function (array $line, int $index): string {
            return sprintf(
                '%d. *%s*: *%d* x *Rp %s* = *Rp %s*',
                $index + 1,
                $line['label'],
                $line['count'],
                number_format($line['rate']),
                number_format($line['total'])
            );
        });

        $messageLines = collect([
            'Selamat pagi. Minta tolong dicek total les berikut ini dan segera konfirmasi jika sudah sesuai agar dapat diproses.',
            '',
            sprintf('Total les sampai akhir *%s %s*:', $this->monthName($month), $year),
            '',
        ])
            ->merge($linesText);

        if ($latePenalty > 0) {
            $messageLines->push('');
            $messageLines->push(sprintf('Total keterlambatan presensi: *%d kali*', $lateCountTotal));
            $messageLines->push(sprintf('Total denda: Rp %s x 10%% x %d = *-Rp %s*', number_format((int) ($latePenalty / $lateCountTotal / 0.1)), $lateCountTotal, number_format($latePenalty)));
        }

        $messageLines->push('');
        $messageLines->push(sprintf('Gaji awal: *Rp %s*', number_format($grandTotal)));

        if ($latePenalty > 0) {
            $messageLines->push(sprintf('Potongan denda: *-Rp %s*', number_format($latePenalty)));
        }

        $messageLines = $messageLines->merge([
            sprintf('Gaji final: *Rp %s*', number_format($finalTotal)),
            '',
            'Apakah nomor rekening tetap? Mohon info jika ada perubahan, dan mohon info perkembangan setiap siswa yang diajar.',
            'Terima kasih sudah mengajar dengan penuh rasa tanggung jawab dan dedikasi.',
        ]);

        return $messageLines->implode("\n");
    }

    private function paymentAccountLines(): Collection
    {
        $accounts = BankAccount::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $lines = collect(['Pembayaran bisa via transfer:']);
        $index = 1;
        foreach ($accounts as $account) {
            $lines->push(sprintf(
                '%d. *%s*: a/n *%s* *%s*',
                $index,
                $account->bank_name,
                $account->account_holder,
                $account->account_number
            ));
            $index++;
        }

        return $lines;
    }

    private function resolveDiscount(int $baseTotal, ?string $type, ?int $value): array
    {
        $type = $type ? strtolower($type) : null;

        if (! $type || $value === null) {
            return [
                'type' => null,
                'value' => null,
                'label' => null,
                'amount' => 0,
                'total' => $baseTotal,
            ];
        }

        $amount = 0;
        $label = null;
        $total = $baseTotal;

        if ($type === 'percent') {
            $percent = max(0, min(100, $value));
            $amount = (int) round($baseTotal * $percent / 100);
            $label = sprintf('%d%%', $percent);
            $total = max(0, $baseTotal - $amount);
        } elseif ($type === 'amount') {
            $amount = min($value, $baseTotal);
            $label = sprintf('Rp %s', number_format($value));
            $total = max(0, $baseTotal - $amount);
        } elseif ($type === 'final') {
            $finalTotal = max(0, min($value, $baseTotal));
            $amount = max(0, $baseTotal - $finalTotal);
            $label = sprintf('Rp %s', number_format($finalTotal));
            $total = $finalTotal;
        }

        return [
            'type' => $type,
            'value' => $value,
            'label' => $label,
            'amount' => $amount,
            'total' => $total,
        ];
    }

    private function enrollmentDiscountsByPeriod(int $month, int $year): Collection
    {
        return EnrollmentStudentDiscount::query()
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy(fn (EnrollmentStudentDiscount $discount) => $this->discountKey($discount->enrollment_id, $discount->student_id));
    }

    private function discountKey(?int $enrollmentId, ?int $studentId): string
    {
        return sprintf('%s-%s', $enrollmentId ?? '0', $studentId ?? '0');
    }
}