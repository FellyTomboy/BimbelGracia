<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\Teacher;
use App\Services\CalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $teacher = Teacher::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        $attendances = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            ->where('month', $month)
            ->where('year', $year)
            ->where(function ($query) use ($teacher) {
                if (!$teacher) {
                    return;
                }
                $query->whereHas('enrollment', fn ($q) => $q->where('teacher_id', $teacher->id));
                $query->orWhere('session_teacher_id', $teacher->id);
            })
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->get();

        $result = app(CalculationService::class)->calculateTeacherSalary($teacher?->id, $month, $year, $attendances);

        $groupedRows = collect($result['rows'])->map(function (array $row) {
            $presentCount = $row['present_count'] ?? 0;
            $isPrivat = ($row['type'] ?? '') === 'privat';
            $isKelas = ($row['type'] ?? '') === 'kelas';
            $countLabel = $isPrivat
                ? ($presentCount > 1 ? sprintf('grup %d orang', $presentCount) : '')
                : ($isKelas ? '1 siswa (kelas)' : 'kelas tanpa murid');

            return [
                'enrollment_id' => $row['enrollment_id'],
                'enrollment' => $row['enrollment'],
                'program' => $row['program'],
                'students' => $row['_students'] ?? collect(),
                'session_count' => $row['count'],
                'gross_rate' => $row['total'],
                'late_count' => $row['late_count'],
                'late_penalty' => $row['penalty'],
                'total_salary' => $row['total'] - $row['penalty'],
                'payment_status' => $row['payment_status'],
                'type' => $row['type'],
                'present_count' => $presentCount,
                'label_detail' => $countLabel,
            ];
        });

        return view('guru.history.index', [
            'month' => $month,
            'year' => $year,
            'teacher' => $teacher,
            'groupedRows' => $groupedRows,
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
    }
}
