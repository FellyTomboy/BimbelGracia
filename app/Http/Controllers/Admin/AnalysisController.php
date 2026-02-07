<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function ortu(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)
            ->get();

        $grouped = $attendances
            ->groupBy('student_id')
            ->map(function ($items) use ($month, $year) {
                $student = $items->first()?->student;
                $lines = $items
                    ->groupBy('lesson_id')
                    ->map(function ($lessonItems) {
                        $attendance = $lessonItems->first();
                        $lesson = $attendance?->lesson;
                        $teacherName = $lesson?->teacher?->name ?? '-';
                        $rate = $lesson?->parent_rate ?? 0;
                        $totalLessons = $lessonItems->sum('total_lessons');
                        $total = $totalLessons * $rate;

                        return [
                            'label' => sprintf('%s (%s)', $teacherName, $lesson?->code ?? '-'),
                            'count' => $totalLessons,
                            'rate' => $rate,
                            'total' => $total,
                        ];
                    })
                    ->values();

                $grandTotal = $lines->sum('total');
                $messageLines = collect([
                    sprintf('Total les sampai akhir %s %s adalah:', $this->monthName($month), $year),
                ])
                    ->merge($lines->map(function (array $line): string {
                        return sprintf('Tentor %s: %d x %s = %s', $line['label'], $line['count'], number_format($line['rate']), number_format($line['total']));
                    }))
                    ->merge([
                        sprintf('Total: %s', number_format($grandTotal)),
                    ]);

                return [
                    'student' => $student,
                    'lines' => $lines,
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

        $attendances = $this->baseAttendanceQuery($month, $year)
            ->get();

        $grouped = $attendances
            ->groupBy('teacher_id')
            ->map(function ($items) use ($month, $year) {
                $teacher = $items->first()?->teacher;
                $lines = $items
                    ->groupBy('lesson_id')
                    ->map(function ($lessonItems) {
                        $attendance = $lessonItems->first();
                        $lesson = $attendance?->lesson;
                        $studentName = $lesson?->student?->name ?? '-';
                        $rate = $lesson?->teacher_rate ?? 0;
                        $totalLessons = $lessonItems->sum('total_lessons');
                        $total = $totalLessons * $rate;

                        return [
                            'label' => sprintf('%s (%s)', $studentName, $lesson?->code ?? '-'),
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
            ->with(['lesson.teacher', 'lesson.student', 'teacher', 'student'])
            ->where('status', 'validated')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('teacher_id');
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
