<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Salary;
use App\Models\Teacher;
use App\Services\AttendanceFineService;
use App\Services\CalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    private const PDF_DISK = 'public';

    public function __construct(
        private CalculationService $calculationService,
        private AttendanceFineService $fineService,
    ) {}

    public function serveParentInvoice(Request $request, int $parent, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $invoice = Invoice::where('parent_id', $parent)
            ->where('filename', $filename)->first();

        $storagePath = sprintf('pdf/invoice/parent_%s/%s', $parent, $filename);

        // Admin: regenerate on-demand dengan data terkini agar selalu fresh
        if ($this->isParentAdmin($request, $parent)) {
            if (preg_match('/Tagihan_(\d{2})-(\d{4})_/', $filename, $matches)) {
                $month = (int) $matches[1];
                $year = (int) $matches[2];
                $this->overwriteParentInvoice($parent, $month, $year, $storagePath);
            }
            if (! $this->isWithinAllowedDirectory($storagePath) || ! Storage::disk(self::PDF_DISK)->exists($storagePath)) {
                abort(404);
            }

            return $this->servePdf($storagePath, $filename, 'inline');
        }

        // Non-admin / guest: cek kelengkapan data
        $redirectTo = route('pdf.parent', [$parent, $filename]);
        if ($this->isParentDataIncomplete($request, $parent)) {
            return redirect()->route('complete-data.parent', [
                'parent' => $parent,
                'redirect_to' => $redirectTo,
            ]);
        }

        // Regenerasi on-demand dengan data terkini jika filename ada di database
        if ($invoice && preg_match('/Tagihan_(\d{2})-(\d{4})_/', $filename, $matches)) {
            $month = (int) $matches[1];
            $year = (int) $matches[2];
            $this->overwriteParentInvoice($parent, $month, $year, $storagePath);
        }

        if (! $this->isWithinAllowedDirectory($storagePath) || ! Storage::disk(self::PDF_DISK)->exists($storagePath)) {
            abort(404);
        }

        return $this->servePdf($storagePath, $filename, 'inline');
    }

    public function serveTeacherSlip(Request $request, int $teacher, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $salary = Salary::where('teacher_id', $teacher)
            ->where('filename', $filename)->first();

        $teacherModel = Teacher::findOrFail($teacher);
        $storagePath = sprintf('pdf/salary/teacher_%s/%s', $teacher, $filename);

        // Admin: regenerate on-demand dengan data terkini agar selalu fresh
        if ($this->isTeacherAdmin($request, $teacher)) {
            if (preg_match('/Slip_Gaji_(\d{2})-(\d{4})_/', $filename, $matches)) {
                $month = (int) $matches[1];
                $year = (int) $matches[2];
                $this->overwriteTeacherSlip($teacher, $month, $year, $storagePath);
            }
            if (! $this->isWithinAllowedDirectory($storagePath) || ! Storage::disk(self::PDF_DISK)->exists($storagePath)) {
                abort(404);
            }

            return $this->servePdf($storagePath, $filename, 'inline');
        }

        // Non-admin / guest: cek kelengkapan data
        $redirectTo = route('pdf.guru', [$teacher, $filename]);
        if ($this->isTeacherDataIncomplete($request, $teacher)) {
            return redirect()->route('complete-data.guru', [
                'teacher' => $teacher,
                'redirect_to' => $redirectTo,
            ]);
        }

        // Regenerasi on-demand dengan data terkini jika filename ada di database
        if ($salary && preg_match('/Slip_Gaji_(\d{2})-(\d{4})_/', $filename, $matches)) {
            $month = (int) $matches[1];
            $year = (int) $matches[2];
            $this->overwriteTeacherSlip($teacher, $month, $year, $storagePath);
        }

        if (! $this->isWithinAllowedDirectory($storagePath) || ! Storage::disk(self::PDF_DISK)->exists($storagePath)) {
            abort(404);
        }

        return $this->servePdf($storagePath, $filename, 'inline');
    }

    private function overwriteParentInvoice(int $parentId, int $month, int $year, string $storagePath): void
    {
        $parent = ParentModel::with('students')->findOrFail($parentId);
        $students = $parent->students()->with('parent')->get();

        $attendances = MonthlyAttendance::with(['enrollment.program', 'enrollment.teacher', 'sessionTeacher', 'students'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $students->pluck('id')))
            ->where(fn ($q) => $q->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
            ->get();

        $teacherOnlyAttendances = MonthlyAttendance::with(['classSession.program', 'students', 'sessionTeacher'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('enrollment_id')
            ->where('parent_rate', '>', 0)
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $students->pluck('id')))
            ->get();

        $attendances = $attendances->merge($teacherOnlyAttendances);

        if ($attendances->isEmpty()) {
            return;
        }

        // Build billing rows
        $allRows = collect();
        $grandGross = 0;
        $grandDiscount = 0;
        $grandPenalty = 0;
        $allPenalties = [];

        foreach ($students as $student) {
            $studentAttendances = $attendances->filter(fn ($a) => $a->students->contains($student->id));
            if ($studentAttendances->isEmpty()) {
                continue;
            }

            $calcResult = $this->calculationService
                ->calculateStudentBilling($student, $month, $year, $studentAttendances);

            $taggedRows = $calcResult['rows']->map(function ($r) use ($student) {
                $r['student_name'] = $student->display_name;
                return $r;
            });
            $allRows = $allRows->concat($taggedRows);
            $grandGross += $calcResult['rows']->sum('subtotal');
            $grandDiscount += $calcResult['total_discount'];
            $grandPenalty += $calcResult['total_penalty'];

            $enrollments = $studentAttendances->groupBy('enrollment_id');
            foreach ($enrollments as $enrollmentAttendances) {
                $first = $enrollmentAttendances->first();
                $enrollment = $first->enrollment;
                $agreed = $enrollment?->agreed_sessions_per_month ?? 4;
                $studentTotalPresent = $enrollmentAttendances->sum(function (MonthlyAttendance $attendance) use ($student) {
                    $s = $attendance->students->firstWhere('id', $student->id);

                    return (int) ($s?->pivot?->total_present ?? 0);
                });
                $totalSessions = $enrollmentAttendances->count();

                if ($enrollment && $enrollment->hasAttendancePenalty($totalSessions, $studentTotalPresent)) {
                    $allPenalties[] = [
                        'student' => $student->display_name,
                        'program' => $enrollment->program?->name ?? '-',
                        'agreed' => $agreed,
                        'attended' => $studentTotalPresent,
                        'total_sessions' => $totalSessions,
                    ];
                }
            }
        }

        $grandTotal = $grandGross - $grandDiscount;
        $parentName = $parent->name ?? 'Orang Tua';
        $monthName = $this->monthName($month);
        $fineService = app(AttendanceFineService::class);
        // Use frozen fine settings from the first attendance record of the first student
        $firstAttendance = $attendances->first();
        $frozenPenalty = $firstAttendance
            ? $this->calculationService->getFrozenPenaltyInfo($firstAttendance)
            : null;
        $attendancePenaltyType = $frozenPenalty['attendance_penalty_type'] ?? $fineService->getAttendancePenaltyType();
        $attendancePenaltyValue = $frozenPenalty['attendance_penalty_value'] ?? $fineService->getAttendancePenaltyValue();

        $pdf = Pdf::loadView('pdf.parent-invoice', [
            'parentName' => $parentName,
            'students' => $students,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $allRows,
            'grandGross' => $grandGross,
            'grandDiscount' => $grandDiscount,
            'grandPenalty' => $grandPenalty,
            'grandTotal' => $grandTotal,
            'penalties' => $allPenalties,
            'attendancePenaltyType' => $attendancePenaltyType,
            'attendancePenaltyValue' => $attendancePenaltyValue,
            'attendancePenaltyDisplayLabel' => $attendancePenaltyType === 'percent'
                ? $attendancePenaltyValue . '%'
                : 'Rp ' . number_format($attendancePenaltyValue),
            'bankAccounts' => BankAccount::where('status', 'active')->orderBy('id')->get(),
        ])->setPaper('a4', 'portrait');

        Storage::disk(self::PDF_DISK)->put($storagePath, $pdf->output());
    }

    private function overwriteTeacherSlip(int $teacherId, int $month, int $year, string $storagePath): void
    {
        $teacher = Teacher::findOrFail($teacherId);

        $attendances = MonthlyAttendance::with(['enrollment.program', 'enrollment.teacher', 'sessionTeacher', 'students'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->where(function ($query) use ($teacher) {
                $query->whereHas('enrollment', fn ($q) => $q
                    ->where('teacher_id', $teacher->id));
                $query->orWhere(fn ($q) => $q
                    ->whereNotNull('class_session_id')
                    ->where('session_teacher_id', $teacher->id));
            })
            ->get();

        $teacherOnlyAttendances = MonthlyAttendance::with(['classSession.program', 'students'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('enrollment_id')
            ->where('session_teacher_id', $teacher->id)
            ->get();

        $attendances = $attendances->merge($teacherOnlyAttendances);

        if ($attendances->isEmpty()) {
            return;
        }

        $result = $this->calculationService
            ->calculateTeacherSalary($teacherId, $month, $year, $attendances);

        $monthName = $this->monthName($month);
        $totalLateCount = $result['rows']->sum('late_count');
        $firstAttendance = $attendances->first();
        $frozenLate = $firstAttendance
            ? $this->calculationService->getFrozenPenaltyInfo($firstAttendance)
            : null;
        $latePenaltyDisplayLabel = ($frozenLate['late_penalty_type'] ?? 'percent') === 'percent'
            ? ($frozenLate['late_penalty_value'] ?? 10) . '%'
            : 'Rp ' . number_format($frozenLate['late_penalty_value'] ?? 0);

        $pdf = Pdf::loadView('pdf.teacher-salary', [
            'teacher' => $teacher,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'rows' => $result['rows'],
            'grandTotal' => $result['grand_total'],
            'totalPenalty' => $result['total_penalty'],
            'totalLateCount' => $totalLateCount,
            'finalTotal' => $result['final_total'],
            'latePenaltyDisplayLabel' => $latePenaltyDisplayLabel,
        ])->setPaper('a4', 'portrait');

        Storage::disk(self::PDF_DISK)->put($storagePath, $pdf->output());
    }

    private function servePdf(string $storagePath, string $filename, string $disposition = 'inline'): \Symfony\Component\HttpFoundation\Response
    {
        $disk = Storage::disk(self::PDF_DISK);

        return response()->file(
            $disk->path($storagePath),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    private function isWithinAllowedDirectory(string $path): bool
    {
        $disk = Storage::disk(self::PDF_DISK);
        $realPath = realpath($disk->path($path));

        if ($realPath === false) {
            return false;
        }

        $root = realpath($disk->path(''));

        return str_starts_with($realPath, $root . DIRECTORY_SEPARATOR)
            && ! str_contains($path, '..');
    }

    private function isParentDataIncomplete(Request $request, int $parentId): bool
    {
        $user = $request->user();
        if ($user && $user->role?->value === 'admin') {
            return false;
        }

        $parent = ParentModel::find($parentId);
        if (! $parent) {
            return false;
        }

        $missingParentData = blank($parent->name) || blank($parent->address);
        $missingStudentData = $parent->students()->where(fn ($q) => $q
            ->where(fn ($q2) => $q2->whereNull('nickname')->orWhereRaw('TRIM(COALESCE(nickname, "")) = ""'))
            ->orWhere(fn ($q2) => $q2->whereNull('full_name')->orWhereRaw('TRIM(COALESCE(full_name, "")) = ""'))
            ->orWhere(fn ($q2) => $q2->whereNull('sekolah')->orWhereRaw('TRIM(COALESCE(sekolah, "")) = ""'))
            ->orWhere(fn ($q2) => $q2->whereNull('kelas')->orWhereRaw('TRIM(COALESCE(kelas, "")) = ""'))
        )->exists();

        return $missingParentData || $missingStudentData;
    }

    private function isTeacherDataIncomplete(Request $request, int $teacherId): bool
    {
        $user = $request->user();
        if ($user && $user->role?->value === 'admin') {
            return false;
        }

        $teacher = Teacher::find($teacherId);
        if (! $teacher) {
            return false;
        }

        $hasMissingIdentity = blank($teacher->nickname) && blank($teacher->full_name);
        $hasMissingProfile = blank($teacher->major) || blank($teacher->subjects) || blank($teacher->address);
        $hasMissingBank = blank($teacher->bank_name) || blank($teacher->bank_account) || blank($teacher->bank_owner);

        return $hasMissingIdentity || $hasMissingProfile || $hasMissingBank;
    }

    private function isParentAdmin(Request $request, int $parentId): bool
    {
        return $request->user() && $request->user()->role?->value === 'admin';
    }

    private function isTeacherAdmin(Request $request, int $teacherId): bool
    {
        return $request->user() && $request->user()->role?->value === 'admin';
    }

    private function monthName(int $month): string
    {
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $names[$month] ?? (string) $month;
    }
}
