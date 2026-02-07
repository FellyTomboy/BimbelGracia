<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(): Response
    {
        return response()->view('admin.export.index');
    }

    public function students(): StreamedResponse
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

        return $this->csv('students', [
            'id', 'name', 'whatsapp', 'address', 'status', 'state', 'created_at',
        ], $rows);
    }

    public function teachers(): StreamedResponse
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

        return $this->csv('teachers', [
            'id', 'name', 'whatsapp', 'major', 'subjects', 'bank_name', 'bank_account', 'bank_owner', 'status', 'state', 'created_at',
        ], $rows);
    }

    public function lessons(): StreamedResponse
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

        return $this->csv('lessons', [
            'id', 'code', 'teacher', 'student', 'parent_rate', 'teacher_rate', 'validation_status', 'status', 'state', 'created_at',
        ], $rows);
    }

    public function attendances(Request $request): StreamedResponse
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

        return $this->csv('attendances', [
            'id', 'period', 'lesson_code', 'teacher', 'student', 'total_lessons', 'parent_rate', 'parent_total', 'teacher_rate', 'teacher_total', 'status', 'parent_payment', 'teacher_payment', 'submitted_at', 'validated_at',
        ], $rows);
    }

    public function classGroups(): StreamedResponse
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

        return $this->csv('class_groups', [
            'id', 'name', 'subject', 'teacher', 'students', 'state', 'created_at',
        ], $rows);
    }

    public function classSessions(): StreamedResponse
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

        return $this->csv('class_sessions', [
            'id', 'date', 'time', 'class_group', 'teacher', 'subject', 'students', 'state',
        ], $rows);
    }

    public function auditLogs(): StreamedResponse
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

        return $this->csv('audit_logs', [
            'id', 'user', 'action', 'type', 'data_id', 'before', 'after', 'created_at',
        ], $rows);
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
}
