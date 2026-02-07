<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $grouped = $rows
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
                $messageLines = collect([
                    sprintf('Total les sampai akhir %s %s adalah:', $this->monthName($month), $year),
                ]);

                foreach ($students as $studentSummary) {
                    $messageLines->push(sprintf('[%s]', $studentSummary['student']?->name ?? 'Murid'));
                    foreach ($studentSummary['lines'] as $line) {
                        $messageLines->push(
                            sprintf('Tentor %s: %d x %s = %s', $line['label'], $line['count'], number_format($line['rate']), number_format($line['total']))
                        );
                    }
                }

                $messageLines->push(sprintf('Total: %s', number_format($grandTotal)));

                return [
                    'contact' => $contact,
                    'students' => $students,
                    'total' => $grandTotal,
                    'message' => $messageLines->implode("\n"),
                ];
            })
            ->values();

        return view('admin.analysis.ortu', [
            'month' => $month,
            'year' => $year,
            'attendances' => $attendances,
            'summaries' => $grouped,
        ]);
    }

    public function guru(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();
        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get();
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
                $messageLines = collect([
                    sprintf('Rekap gaji sampai akhir %s %s:', $this->monthName($month), $year),
                ])
                    ->merge($lines->map(function (array $line): string {
                        return sprintf('Murid %s: %d x %s = %s', $line['label'], $line['count'], number_format($line['rate']), number_format($line['total']));
                    }))
                    ->merge([
                        sprintf('Total: %s', number_format($grandTotal)),
                    ]);

                return [
                    'teacher' => $teacher,
                    'lines' => $lines,
                    'total' => $grandTotal,
                    'message' => $messageLines->implode("\n"),
                ];
            })
            ->values();

        return view('admin.analysis.guru', [
            'month' => $month,
            'year' => $year,
            'attendances' => $attendances,
            'summaries' => $grouped,
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
}
