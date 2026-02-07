<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
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
        return response()->view('admin.export.index');
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

        return $this->pdf('Les Privat', $headers, $rows, 'lessons');
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
                    $student->whatsapp,
                    $student->address,
                    $student->status,
                    $student->deleted_at ? 'hibernasi' : 'active',
                    optional($student->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'name', 'whatsapp', 'address', 'status', 'state', 'created_at'],
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
                    $teacher->whatsapp,
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
            ['id', 'name', 'whatsapp', 'major', 'subjects', 'bank_name', 'bank_account', 'bank_owner', 'status', 'state', 'created_at'],
            $rows,
        ];
    }

    private function lessonsData(): array
    {
        $rows = Lesson::withTrashed()
            ->with(['teacher', 'student'])
            ->orderBy('code')
            ->get()
            ->map(function (Lesson $lesson) {
                return [
                    $lesson->id,
                    $lesson->code,
                    $lesson->teacher?->name,
                    $lesson->student?->name,
                    $lesson->parent_rate,
                    $lesson->teacher_rate,
                    $lesson->validation_status,
                    $lesson->status,
                    $lesson->deleted_at ? 'hibernasi' : 'active',
                    optional($lesson->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'code', 'teacher', 'student', 'parent_rate', 'teacher_rate', 'validation_status', 'status', 'state', 'created_at'],
            $rows,
        ];
    }

    private function attendancesData(Request $request): array
    {
        $query = MonthlyAttendance::with(['lesson.teacher', 'lesson.student', 'teacher', 'student']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->integer('teacher_id'));
        }

        $rows = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function (MonthlyAttendance $attendance) {
                $parentRate = $attendance->lesson?->parent_rate ?? 0;
                $teacherRate = $attendance->lesson?->teacher_rate ?? 0;
                return [
                    $attendance->id,
                    sprintf('%02d/%d', $attendance->month, $attendance->year),
                    $attendance->lesson?->code,
                    $attendance->teacher?->name,
                    $attendance->student?->name,
                    $attendance->total_lessons,
                    $parentRate,
                    $attendance->total_lessons * $parentRate,
                    $teacherRate,
                    $attendance->total_lessons * $teacherRate,
                    $attendance->status,
                    $attendance->parent_payment_status,
                    $attendance->teacher_payment_status,
                    optional($attendance->submitted_at)->toDateTimeString(),
                    optional($attendance->validated_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            ['id', 'period', 'lesson_code', 'teacher', 'student', 'total_lessons', 'parent_rate', 'parent_total', 'teacher_rate', 'teacher_total', 'status', 'parent_payment', 'teacher_payment', 'submitted_at', 'validated_at'],
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
}
