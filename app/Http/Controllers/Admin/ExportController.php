<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(): Response
    {
        $students = Student::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();

        return response()->view('admin.export.index', [
            'students' => $students,
            'teachers' => $teachers,
        ]);
    }

    public function students(): StreamedResponse
    {
        [$headers, $rows] = $this->studentsData();

        return $this->csv('students', $headers, $rows);
    }

    public function studentsExcel(): Response
    {
        [$headers, $rows] = $this->studentsData();

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('students', 'xlsx'));
    }

    public function studentsPdf(): Response
    {
        [$headers, $rows] = $this->studentsData();

        return $this->pdf('Murid', $headers, $rows, 'students');
    }

    public function teachers(): StreamedResponse
    {
        [$headers, $rows] = $this->teachersData();

        return $this->csv('teachers', $headers, $rows);
    }

    public function teachersExcel(): Response
    {
        [$headers, $rows] = $this->teachersData();

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('teachers', 'xlsx'));
    }

    public function teachersPdf(): Response
    {
        [$headers, $rows] = $this->teachersData();

        return $this->pdf('Guru', $headers, $rows, 'teachers');
    }

    public function lessons(): StreamedResponse
    {
        [$headers, $rows] = $this->lessonsData();

        return $this->csv('lessons', $headers, $rows);
    }

    public function lessonsExcel(): Response
    {
        [$headers, $rows] = $this->lessonsData();

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('lessons', 'xlsx'));
    }

    public function lessonsPdf(): Response
    {
        [$headers, $rows] = $this->lessonsData();

        return $this->pdf('Enrollment', $headers, $rows, 'lessons');
    }

    public function attendances(Request $request): StreamedResponse
    {
        [$headers, $rows] = $this->attendancesData($request);

        return $this->csv('attendances', $headers, $rows);
    }

    public function attendancesExcel(Request $request): Response
    {
        [$headers, $rows] = $this->attendancesData($request);

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('attendances', 'xlsx'));
    }

    public function attendancesPdf(Request $request): Response
    {
        [$headers, $rows] = $this->attendancesData($request);

        return $this->pdf('Presensi', $headers, $rows, 'attendances');
    }

    public function classGroups(): StreamedResponse
    {
        [$headers, $rows] = $this->classGroupsData();

        return $this->csv('class_groups', $headers, $rows);
    }

    public function classGroupsExcel(): Response
    {
        [$headers, $rows] = $this->classGroupsData();

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('class_groups', 'xlsx'));
    }

    public function classGroupsPdf(): Response
    {
        [$headers, $rows] = $this->classGroupsData();

        return $this->pdf('Kelas Bersama', $headers, $rows, 'class_groups');
    }

    public function classSessions(): StreamedResponse
    {
        [$headers, $rows] = $this->classSessionsData();

        return $this->csv('class_sessions', $headers, $rows);
    }

    public function classSessionsExcel(): Response
    {
        [$headers, $rows] = $this->classSessionsData();

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('class_sessions', 'xlsx'));
    }

    public function classSessionsPdf(): Response
    {
        [$headers, $rows] = $this->classSessionsData();

        return $this->pdf('Jadwal Kelas', $headers, $rows, 'class_sessions');
    }

    public function auditLogs(): StreamedResponse
    {
        [$headers, $rows] = $this->auditLogsData();

        return $this->csv('audit_logs', $headers, $rows);
    }

    public function auditLogsExcel(): Response
    {
        [$headers, $rows] = $this->auditLogsData();

        return Excel::download(new ArrayExport($headers, $rows), $this->filename('audit_logs', 'xlsx'));
    }

    public function auditLogsPdf(): Response
    {
        [$headers, $rows] = $this->auditLogsData();

        return $this->pdf('Audit Log', $headers, $rows, 'audit_logs');
    }

    public function attendancesMonthlyExcel(Request $request): Response
    {
        [$month, $year] = $this->resolvePeriod($request);
        [$headers, $rows] = $this->monthlyAttendancesData($month, $year);

        return Excel::download(
            new ArrayExport($headers, $rows),
            $this->filename(sprintf('attendances_%04d_%02d', $year, $month), 'xlsx')
        );
    }

    public function attendancesMonthlyPdf(Request $request): Response
    {
        [$month, $year] = $this->resolvePeriod($request);
        [$headers, $rows] = $this->monthlyAttendancesData($month, $year);

        return $this->pdf(
            sprintf('Presensi Bulanan %02d/%04d', $month, $year),
            $headers,
            $rows,
            sprintf('attendances_%04d_%02d', $year, $month)
        );
    }

    public function classSessionsMonthlyExcel(Request $request): Response
    {
        [$month, $year] = $this->resolvePeriod($request);
        [$headers, $rows] = $this->monthlyClassSessionsData($month, $year);

        return Excel::download(
            new ArrayExport($headers, $rows),
            $this->filename(sprintf('class_sessions_%04d_%02d', $year, $month), 'xlsx')
        );
    }

    public function classSessionsMonthlyPdf(Request $request): Response
    {
        [$month, $year] = $this->resolvePeriod($request);
        [$headers, $rows] = $this->monthlyClassSessionsData($month, $year);

        return $this->pdf(
            sprintf('Jadwal Kelas %02d/%04d', $month, $year),
            $headers,
            $rows,
            sprintf('class_sessions_%04d_%02d', $year, $month)
        );
    }

    public function backupDatabase(): RedirectResponse|Response
    {
        $connection = DB::getDefaultConnection();

        if ($connection !== 'sqlite') {
            return back()->withErrors(['backup' => 'Backup otomatis hanya tersedia untuk sqlite.']);
        }

        $path = DB::connection()->getDatabaseName();

        if (! $path || ! File::exists($path)) {
            return back()->withErrors(['backup' => 'File database tidak ditemukan.']);
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $filename = 'backup_'.now()->format('Ymd_His').'.sqlite';
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        File::copy($path, $backupPath);

        return response()->download($backupPath)->deleteFileAfterSend(true);
    }

    private function csv(string $name, array $headers, array $rows): StreamedResponse
    {
        $filename = $name.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function pdf(string $title, array $headers, array $rows, string $name): Response
    {
        $pdf = Pdf::loadView('admin.export.pdf-table', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename($name, 'pdf'));
    }

    private function filename(string $name, string $extension): string
    {
        return $name.'_'.now()->format('Ymd_His').'.'.$extension;
    }

    private function studentsData(): array
    {
        $rows = Student::withTrashed()
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                return [
                    $student->id,
                    $student->name,
                    $student->whatsapp_primary,
                    $student->whatsapp_secondary,
                    $student->address,
                    $student->status,
                    $student->deleted_at ? 'hibernasi' : 'active',
                    optional($student->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'name', 'whatsapp_primary', 'whatsapp_secondary', 'address', 'status', 'state', 'created_at'],
            $rows,
        ];
    }

    private function teachersData(): array
    {
        $rows = Teacher::withTrashed()
            ->orderBy('name')
            ->get()
            ->map(function (Teacher $teacher) {
                return [
                    $teacher->id,
                    $teacher->name,
                    $teacher->whatsapp_number,
                    $teacher->major,
                    $teacher->subjects,
                    $teacher->bank_name,
                    $teacher->bank_account,
                    $teacher->bank_owner,
                    $teacher->status,
                    $teacher->deleted_at ? 'hibernasi' : 'active',
                    optional($teacher->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'name', 'whatsapp_number', 'major', 'subjects', 'bank_name', 'bank_account', 'bank_owner', 'status', 'state', 'created_at'],
            $rows,
        ];
    }

    private function lessonsData(): array
    {
        $rows = Enrollment::withTrashed()
            ->with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get()
            ->map(function (Enrollment $enrollment) {
                return [
                    $enrollment->id,
                    $enrollment->program?->name,
                    $enrollment->program?->type,
                    $enrollment->teacher?->name,
                    $enrollment->students->pluck('name')->implode(', '),
                    $enrollment->parent_rate,
                    $enrollment->teacher_rate,
                    $enrollment->validation_status,
                    $enrollment->status,
                    $enrollment->deleted_at ? 'hibernasi' : 'active',
                    optional($enrollment->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'program', 'type', 'teacher', 'students', 'parent_rate', 'teacher_rate', 'validation_status', 'status', 'state', 'created_at'],
            $rows,
        ];
    }

    private function attendancesData(Request $request): array
    {
        $query = MonthlyAttendance::with(['enrollment.program', 'enrollment.teacher', 'students']);

        if ($request->filled('student_id')) {
            $query->whereHas('students', fn ($sub) => $sub->where('students.id', $request->integer('student_id')));
        }

        if ($request->filled('teacher_id')) {
            $query->whereHas('enrollment', fn ($sub) => $sub->where('teacher_id', $request->integer('teacher_id')));
        }

        $rows = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function (MonthlyAttendance $attendance) {
                $parentRate = $attendance->enrollment?->parent_rate ?? 0;
                $teacherRate = $attendance->enrollment?->teacher_rate ?? 0;
                $parentTotal = $attendance->students
                    ->sum(fn ($student) => (int) ($student->pivot?->total_present ?? 0) * $parentRate);
                $studentNames = $attendance->students->pluck('name')->implode(', ');
                return [
                    $attendance->id,
                    sprintf('%02d/%d', $attendance->month, $attendance->year),
                    $attendance->enrollment_id,
                    $attendance->enrollment?->program?->name,
                    $attendance->enrollment?->teacher?->name,
                    $studentNames,
                    $attendance->total_lessons,
                    $parentRate,
                    $parentTotal,
                    $teacherRate,
                    $attendance->total_lessons * $teacherRate,
                    $attendance->status_validation,
                    $attendance->parent_payment_status,
                    $attendance->teacher_payment_status,
                    optional($attendance->created_at)->toDateTimeString(),
                    optional($attendance->validated_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'period', 'enrollment_id', 'program', 'teacher', 'students', 'total_lessons', 'parent_rate', 'parent_total', 'teacher_rate', 'teacher_total', 'status_validation', 'parent_payment', 'teacher_payment', 'created_at', 'validated_at'],
            $rows,
        ];
    }

    private function classGroupsData(): array
    {
        $rows = ClassGroup::withTrashed()
            ->with(['teacher', 'students'])
            ->orderBy('name')
            ->get()
            ->map(function (ClassGroup $group) {
                return [
                    $group->id,
                    $group->name,
                    $group->subject,
                    $group->teacher?->name,
                    $group->students->count(),
                    $group->deleted_at ? 'hibernasi' : 'active',
                    optional($group->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'name', 'subject', 'teacher', 'students', 'state', 'created_at'],
            $rows,
        ];
    }

    private function classSessionsData(): array
    {
        $rows = ClassSession::withTrashed()
            ->with(['classGroup', 'teacher', 'students'])
            ->orderByDesc('session_date')
            ->get()
            ->map(function (ClassSession $session) {
                return [
                    $session->id,
                    optional($session->session_date)->format('Y-m-d'),
                    $session->session_time?->format('H:i'),
                    $session->classGroup?->name,
                    $session->teacher?->name,
                    $session->subject,
                    $session->students->count(),
                    $session->deleted_at ? 'hibernasi' : 'active',
                ];
            })
            ->all();

        return [
            ['id', 'date', 'time', 'class_group', 'teacher', 'subject', 'students', 'state'],
            $rows,
        ];
    }

    private function auditLogsData(): array
    {
        $rows = AuditLog::with('user')
            ->latest()
            ->get()
            ->map(function (AuditLog $log) {
                return [
                    $log->id,
                    $log->user?->name,
                    $log->action,
                    $log->auditable_type,
                    $log->auditable_id,
                    json_encode($log->before),
                    json_encode($log->after),
                    optional($log->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'user', 'action', 'type', 'data_id', 'before', 'after', 'created_at'],
            $rows,
        ];
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
    }

    private function monthlyAttendancesData(int $month, int $year): array
    {
        $rows = MonthlyAttendance::with(['enrollment.program', 'enrollment.teacher', 'students'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('enrollment_id')
            ->get()
            ->map(function (MonthlyAttendance $attendance) {
                $parentRate = $attendance->enrollment?->parent_rate ?? 0;
                $teacherRate = $attendance->enrollment?->teacher_rate ?? 0;
                $parentTotal = $attendance->students
                    ->sum(fn ($student) => (int) ($student->pivot?->total_present ?? 0) * $parentRate);
                $studentNames = $attendance->students->pluck('name')->implode(', ');
                return [
                    $attendance->id,
                    sprintf('%02d/%d', $attendance->month, $attendance->year),
                    $attendance->enrollment_id,
                    $attendance->enrollment?->program?->name,
                    $attendance->enrollment?->teacher?->name,
                    $studentNames,
                    $attendance->total_lessons,
                    $parentRate,
                    $parentTotal,
                    $teacherRate,
                    $attendance->total_lessons * $teacherRate,
                    $attendance->status_validation,
                    $attendance->parent_payment_status,
                    $attendance->teacher_payment_status,
                    optional($attendance->created_at)->toDateTimeString(),
                    optional($attendance->validated_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'period', 'enrollment_id', 'program', 'teacher', 'students', 'total_lessons', 'parent_rate', 'parent_total', 'teacher_rate', 'teacher_total', 'status_validation', 'parent_payment', 'teacher_payment', 'created_at', 'validated_at'],
            $rows,
        ];
    }

    private function monthlyClassSessionsData(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $rows = ClassSession::with(['classGroup', 'teacher'])
            ->withCount(['students as total_students_count'])
            ->withCount(['students as present_students_count' => function ($query) {
                $query->wherePivot('is_present', true);
            }])
            ->whereBetween('session_date', [$start, $end])
            ->orderBy('session_date')
            ->get()
            ->map(function (ClassSession $session) {
                return [
                    $session->id,
                    optional($session->session_date)->format('Y-m-d'),
                    $session->session_time?->format('H:i'),
                    $session->classGroup?->name,
                    $session->teacher?->name,
                    $session->subject,
                    $session->present_students_count,
                    $session->total_students_count,
                ];
            })
            ->all();

        return [
            ['id', 'date', 'time', 'class_group', 'teacher', 'subject', 'present_students', 'total_students'],
            $rows,
        ];
    }
}
