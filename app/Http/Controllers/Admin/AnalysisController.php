<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ClassStudentDiscount;
use App\Models\ClassStudentSession;
use App\Models\EnrollmentStudentDiscount;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function ortu(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $rows = $this->attendanceRows($attendances);
        $discounts = $this->enrollmentDiscountsByPeriod($month, $year);

        $privatSummaries = $rows
            ->groupBy(function (array $row) {
                return $row['student']->whatsapp_primary
                    ?? $row['student']->whatsapp
                    ?? 'unknown';
            })
            ->map(function (Collection $items, string $contact) use ($month, $year, $discounts) {
                $students = $items
                    ->groupBy(fn (array $row) => $row['student']->id)
                    ->map(function (Collection $studentItems) use ($discounts) {
                        $student = $studentItems->first()['student'];
                        $studentId = $student?->id;
                        $lines = $studentItems
                            ->groupBy(fn (array $row) => $row['enrollment']->id)
                            ->map(function (Collection $enrollmentItems) use ($discounts, $studentId) {
                                $row = $enrollmentItems->first();
                                $teacherName = $row['teacher']?->name ?? '-';
                                $programName = $row['program']?->name ?? '-';
                                $rate = $row['parent_rate'];
                                $count = $enrollmentItems->sum('total_present');
                                $total = $count * $rate;
                                $enrollmentId = $row['enrollment']?->id;
                                $discountModel = $discounts[$this->discountKey($enrollmentId, $studentId)] ?? null;
                                $discount = $this->resolveDiscount($total, $discountModel?->discount_type, $discountModel?->discount_value);

                                return [
                                    'label' => sprintf('%s (%s)', $teacherName, $programName),
                                    'count' => $count,
                                    'rate' => $rate,
                                    'total' => $total,
                                    'total_after' => $discount['total'],
                                    'discount' => $discount,
                                    'enrollment_id' => $enrollmentId,
                                    'student_id' => $studentId,
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

    public function ortuKelas(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);
        $classSummaries = $this->classParentSummaries($month, $year);

        return view('admin.analysis.ortu-class', [
            'month' => $month,
            'year' => $year,
            'classSummaries' => $classSummaries,
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
                        $studentNames = $enrollmentItems
                            ->pluck('student')
                            ->filter()
                            ->pluck('name')
                            ->unique()
                            ->implode(', ');
                        $programName = $row['program']?->name ?? '-';
                        $rate = $row['teacher_rate'];
                        $totalLessons = $row['attendance']->total_lessons;
                        $total = $totalLessons * $rate;

                        return [
                            'label' => sprintf('%s (%s)', $studentNames ?: '-', $programName),
                            'count' => $totalLessons,
                            'rate' => $rate,
                            'total' => $total,
                        ];
                    })
                    ->values();

                $grandTotal = $lines->sum('total');
                $message = $this->buildTeacherMessage($teacher, $lines, $month, $year, $grandTotal);

                return [
                    'teacher' => $teacher,
                    'lines' => $lines,
                    'total' => $grandTotal,
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

        return view('admin.payments.ortu', [
            'month' => $month,
            'year' => $year,
            'attendances' => $attendances,
        ]);
    }

    public function paymentsGuru(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get();

        return view('admin.payments.guru', [
            'month' => $month,
            'year' => $year,
            'attendances' => $attendances,
            'enrollments' => $enrollments,
        ]);
    }

    public function updateParentPayment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'parent_payment_status' => ['required', 'in:unpaid,paid,partial'],
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

    public function updateClassDiscount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'class_student_id' => ['required', 'integer', 'exists:class_students,id'],
            'discount_type' => ['required', 'in:none,percent,final,amount'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = $validated['discount_type'];
        $value = $validated['discount_value'];

        if ($type === 'none' || $value === null || $value === 0) {
            ClassStudentDiscount::query()
                ->where('class_student_id', $validated['class_student_id'])
                ->where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->delete();

            return back()->with('status', 'Diskon kelas dihapus.');
        }

        ClassStudentDiscount::updateOrCreate(
            [
                'class_student_id' => $validated['class_student_id'],
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'discount_type' => $type,
                'discount_value' => $value,
            ]
        );

        return back()->with('status', 'Diskon kelas diperbarui.');
    }

    private function baseAttendanceQuery(int $month, int $year)
    {
        return MonthlyAttendance::query()
            ->with(['enrollment.program', 'enrollment.teacher', 'students'])
            ->where('status_validation', 'valid')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('enrollment_id');
    }

    private function attendanceRows(Collection $attendances): Collection
    {
        return $attendances->flatMap(function (MonthlyAttendance $attendance) {
            $enrollment = $attendance->enrollment;
            $program = $enrollment?->program;
            $teacher = $enrollment?->teacher;

            return $attendance->students->map(function (Student $student) use ($attendance, $enrollment, $program, $teacher) {
                return [
                    'attendance' => $attendance,
                    'enrollment' => $enrollment,
                    'program' => $program,
                    'teacher' => $teacher,
                    'student' => $student,
                    'total_present' => (int) ($student->pivot?->total_present ?? 0),
                    'parent_rate' => (int) ($enrollment?->parent_rate ?? 0),
                    'teacher_rate' => (int) ($enrollment?->teacher_rate ?? 0),
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
            $lines->push(sprintf('%d. *%s*', $index, $studentSummary['student']?->name ?? 'Murid'));
            foreach ($studentSummary['lines'] as $line) {
                $lines->push(
                    sprintf(
                        '   - Tentor *%s*: *%d* x *Rp %s* = *Rp %s*',
                        $line['label'],
                        $line['count'],
                        number_format($line['rate']),
                        number_format($line['total'])
                    )
                );
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
        $lines->push('Mohon dicek kembali.');

        $paymentLines = $this->paymentAccountLines();
        if ($paymentLines->isNotEmpty()) {
            $lines->push('');
            $lines = $lines->merge($paymentLines);
        }

        $lines->push('');
        $lines->push('Mohon konfirmasi jika sudah transfer.');
        $lines->push('Jika ada kritik/saran untuk tentor/bimbel, atau ingin mengetahui perkembangan siswa, kami terbuka untuk berdiskusi lewat WhatsApp.');
        $lines->push('Terima kasih atas perhatiannya.');

        return $lines->implode("\n");
    }

    private function buildClassParentMessage(Collection $students, int $month, int $year, int $grandTotal): string
    {
        $studentNames = $students
            ->pluck('student')
            ->filter()
            ->pluck('name')
            ->implode(', ');

        $lines = collect([
            sprintf('Selamat pagi Bapak/Ibu dari siswa *%s*.', $studentNames ?: 'Murid'),
            '',
            sprintf('Total biaya les pada Bulan *%s* sejumlah:', $this->monthName($month)),
            '',
        ]);

        $index = 1;
        foreach ($students as $studentSummary) {
            $studentName = $studentSummary['student']?->name ?? 'Murid';
            $lines->push(sprintf(
                '%d. *%s*: *Rp %s* x *%d* = *Rp %s*',
                $index,
                $studentName,
                number_format($studentSummary['rate']),
                $studentSummary['count'],
                number_format($studentSummary['total'])
            ));
            $discount = $studentSummary['discount'] ?? null;
            if ($discount && $discount['type']) {
                if ($discount['type'] === 'final') {
                    $lines->push(sprintf('   - Total akhir: *Rp %s*', number_format($discount['total'])));
                } else {
                    $lines->push(sprintf('   - Diskon *%s*: -*Rp %s*', $discount['label'], number_format($discount['amount'])));
                    $lines->push(sprintf('   - Total setelah diskon: *Rp %s*', number_format($discount['total'])));
                }
            }
            $index++;
        }

        $lines->push('');
        $lines->push(sprintf('Total: *Rp %s*', number_format($grandTotal)));
        $lines->push('');
        $lines->push('Mohon dicek kembali.');

        $paymentLines = $this->paymentAccountLines();
        if ($paymentLines->isNotEmpty()) {
            $lines->push('');
            $lines = $lines->merge($paymentLines);
        }

        $lines->push('');
        $lines->push('Mohon konfirmasi jika sudah transfer.');
        $lines->push('Jika ada kritik/saran untuk tentor/bimbel, atau ingin mengetahui perkembangan siswa, kami terbuka untuk berdiskusi lewat WhatsApp.');
        $lines->push('Terima kasih atas perhatiannya.');

        return $lines->implode("\n");
    }

    private function buildTeacherMessage(?\App\Models\Teacher $teacher, Collection $lines, int $month, int $year, int $grandTotal): string
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
            ->merge($linesText)
            ->merge([
                '',
                sprintf('Total gaji Anda sebesar: *Rp %s*', number_format($grandTotal)),
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

    private function classParentSummaries(int $month, int $year): Collection
    {
        $discounts = $this->classDiscountsByPeriod($month, $year);
        $classSessions = ClassStudentSession::with('student')
            ->whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->get();

        return $classSessions
            ->groupBy(function (ClassStudentSession $session) {
                $student = $session->student;

                return $student?->whatsapp_primary
                    ?? $student?->whatsapp_secondary
                    ?? 'unknown';
            })
            ->map(function (Collection $sessions, string $contact) use ($month, $year) {
                $students = $sessions
                    ->groupBy(fn (ClassStudentSession $session) => $session->class_student_id)
                    ->map(function (Collection $studentSessions) use ($discounts) {
                        $student = $studentSessions->first()?->student;
                        $count = $studentSessions->count();
                        $rate = (int) ($student?->rate_per_meeting ?? 0);
                        $total = $count * $rate;
                        $discountModel = $discounts[$student?->id ?? 0] ?? null;
                        $discount = $this->resolveDiscount($total, $discountModel?->discount_type, $discountModel?->discount_value);

                        return [
                            'student' => $student,
                            'count' => $count,
                            'rate' => $rate,
                            'total' => $total,
                            'total_after' => $discount['total'],
                            'discount' => $discount,
                            'class_student_id' => $student?->id,
                        ];
                    })
                    ->values();

                $grandTotal = $students->sum('total_after');
                $message = $this->buildClassParentMessage($students, $month, $year, $grandTotal);

                return [
                    'contact' => $contact,
                    'students' => $students,
                    'total' => $grandTotal,
                    'message' => $message,
                ];
            })
            ->values();
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

    private function classDiscountsByPeriod(int $month, int $year): Collection
    {
        return ClassStudentDiscount::query()
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('class_student_id');
    }

    private function discountKey(?int $enrollmentId, ?int $studentId): string
    {
        return sprintf('%s-%s', $enrollmentId ?? '0', $studentId ?? '0');
    }
}
