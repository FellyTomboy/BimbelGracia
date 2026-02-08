<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassStudentSession;
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

        $privatSummaries = $rows
            ->groupBy(function (array $row) {
                return $row['student']->whatsapp_primary
                    ?? $row['student']->whatsapp
                    ?? 'unknown';
            })
            ->map(function (Collection $items, string $contact) use ($month, $year) {
                $students = $items
                    ->groupBy(fn (array $row) => $row['student']->id)
                    ->map(function (Collection $studentItems) {
                        $student = $studentItems->first()['student'];
                        $lines = $studentItems
                            ->groupBy(fn (array $row) => $row['enrollment']->id)
                            ->map(function (Collection $enrollmentItems) {
                                $row = $enrollmentItems->first();
                                $teacherName = $row['teacher']?->name ?? '-';
                                $programName = $row['program']?->name ?? '-';
                                $rate = $row['parent_rate'];
                                $count = $enrollmentItems->sum('total_present');
                                $total = $count * $rate;

                                return [
                                    'label' => sprintf('%s (%s)', $teacherName, $programName),
                                    'count' => $count,
                                    'rate' => $rate,
                                    'total' => $total,
                                ];
                            })
                            ->values();

                        return [
                            'student' => $student,
                            'lines' => $lines,
                            'total' => $lines->sum('total'),
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

        $classSessions = ClassStudentSession::with('student')
            ->whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->get();

        $classSummaries = $classSessions
            ->groupBy(function (ClassStudentSession $session) {
                $student = $session->student;

                return $student?->whatsapp_primary
                    ?? $student?->whatsapp_secondary
                    ?? 'unknown';
            })
            ->map(function (Collection $sessions, string $contact) use ($month, $year) {
                $students = $sessions
                    ->groupBy(fn (ClassStudentSession $session) => $session->class_student_id)
                    ->map(function (Collection $studentSessions) {
                        $student = $studentSessions->first()?->student;
                        $count = $studentSessions->count();
                        $rate = (int) ($student?->rate_per_meeting ?? 0);
                        $total = $count * $rate;

                        return [
                            'student' => $student,
                            'count' => $count,
                            'rate' => $rate,
                            'total' => $total,
                        ];
                    })
                    ->values();

                $grandTotal = $students->sum('total');
                $message = $this->buildClassParentMessage($students, $month, $year, $grandTotal);

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
            sprintf('Total les sampai akhir %s %s adalah:', $this->monthName($month), $year),
        ]);

        foreach ($students as $studentSummary) {
            $lines->push(sprintf('[%s]', $studentSummary['student']?->name ?? 'Murid'));
            foreach ($studentSummary['lines'] as $line) {
                $lines->push(
                    sprintf(
                        'Tentor %s: %d x Rp %s = Rp %s',
                        $line['label'],
                        $line['count'],
                        number_format($line['rate']),
                        number_format($line['total'])
                    )
                );
            }
        }

        $lines->push(sprintf('Total pembayaran sebesar: Rp %s', number_format($grandTotal)));
        $lines->push('Mohon dicek kembali.');

        $lines = $lines->merge($this->paymentAccountLines());
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
            sprintf('Selamat pagi Bapak/Ibu dari siswa %s.', $studentNames ?: 'Murid'),
            sprintf('Total biaya les pada Bulan %s sejumlah:', $this->monthName($month)),
        ]);

        foreach ($students as $studentSummary) {
            $studentName = $studentSummary['student']?->name ?? 'Murid';
            $lines->push(sprintf('%s: Rp %s x %d = Rp %s',
                $studentName,
                number_format($studentSummary['rate']),
                $studentSummary['count'],
                number_format($studentSummary['total'])
            ));
        }

        $lines->push(sprintf('Total: Rp %s', number_format($grandTotal)));
        $lines->push('Mohon dicek kembali.');
        $lines = $lines->merge($this->paymentAccountLines());
        $lines->push('Mohon konfirmasi jika sudah transfer.');
        $lines->push('Jika ada kritik/saran untuk tentor/bimbel, atau ingin mengetahui perkembangan siswa, kami terbuka untuk berdiskusi lewat WhatsApp.');
        $lines->push('Terima kasih atas perhatiannya.');

        return $lines->implode("\n");
    }

    private function buildTeacherMessage(?\App\Models\Teacher $teacher, Collection $lines, int $month, int $year, int $grandTotal): string
    {
        $linesText = $lines->map(function (array $line): string {
            return sprintf('%s: %d x Rp %s = Rp %s', $line['label'], $line['count'], number_format($line['rate']), number_format($line['total']));
        });

        $messageLines = collect([
            'Selamat pagi. Minta tolong dicek total les berikut ini dan segera konfirmasi jika sudah sesuai agar dapat diproses.',
            sprintf('Total les sampai akhir %s %s:', $this->monthName($month), $year),
        ])
            ->merge($linesText)
            ->merge([
                sprintf('Total gaji Anda sebesar: Rp %s', number_format($grandTotal)),
                'Apakah nomor rekening tetap? Mohon info jika ada perubahan, dan mohon info perkembangan setiap siswa yang diajar.',
                'Terima kasih sudah mengajar dengan penuh rasa tanggung jawab dan dedikasi.',
            ]);

        return $messageLines->implode("\n");
    }

    private function paymentAccountLines(): Collection
    {
        $accounts = config('bimbel.payment_accounts', []);
        if (empty($accounts)) {
            return collect();
        }

        $lines = collect(['Pembayaran bisa via transfer:']);
        $index = 1;
        foreach ($accounts as $account) {
            $bank = $account['bank'] ?? '';
            $name = $account['name'] ?? '';
            $number = $account['number'] ?? '';

            $lines->push(sprintf('%d. %s: a/n %s %s', $index, $bank, $name, $number));
            $index++;
        }

        return $lines;
    }
}
