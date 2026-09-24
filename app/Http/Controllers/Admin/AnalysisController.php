<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ClassSession;
use App\Models\EnrollmentStudentDiscount;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Invoice;
use App\Models\ParentModel;
use App\Models\Salary;
use App\Models\PaymentProof;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\WaNotificationLog;
use App\Services\CalculationService;
use App\Services\Pdf\InvoiceService;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function __construct(
        private CalculationService $calculationService,
    ) {}

    public function ortu(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();

        $waLogs = WaNotificationLog::where('month', $month)
            ->where('year', $year)
            ->whereNotNull('parent_id')
            ->get()
            ->keyBy('parent_id');

        // Group all attendances by parent → student
        $parentStudentAttendances = [];
        foreach ($attendances as $attendance) {
            foreach ($attendance->students as $student) {
                if (($student->pivot->total_present ?? 0) <= 0) {
                    continue;
                }
                $parent = $student->parent;
                $parentId = $parent?->id ?? 'unknown';
                $studentId = $student->id;
                $parentStudentAttendances[$parentId][$studentId]['student'] = $student;
                $parentStudentAttendances[$parentId][$studentId]['attendances'][] = $attendance;
            }
        }

        $parentIds = array_keys($parentStudentAttendances);
        $parents = ParentModel::whereIn('id', $parentIds)->get()->keyBy('id');

        $invoices = Invoice::where('month', $month)
            ->where('year', $year)
            ->whereIn('parent_id', $parentIds)
            ->get()
            ->keyBy('parent_id');

        $privatSummaries = collect($parentStudentAttendances)
            ->map(function (array $studentsData, string $parentId) use ($month, $year, $parents, $waLogs, $invoices) {
                $parent = $parents->get((int) $parentId);
                $contact = $parent?->user?->phone ?? 'unknown';
                $parentName = $parent?->name ?? 'Unknown';
                $waLog = $waLogs[(int) $parentId] ?? null;

                $students = collect($studentsData)->map(function (array $data) use ($month, $year) {
                    $student = $data['student'];
                    $studentAttendances = collect($data['attendances']);

                    $result = $this->calculationService->calculateStudentBilling($student, $month, $year, $studentAttendances);

                    $lines = collect($result['rows'])->map(function (array $row) use ($student) {
                        $discount = $row['discount'] ?? [];
                        $discountAmount = $discount['amount'] ?? 0;
                        $discountTotal = $discount['total'] ?? $row['total'];
                        $program = $row['program'] ?? '-';
                        // For kelas: program + detail. For privat: teacher - program + group note.
                        $label = $row['type'] === 'kelas'
                            ? $program . ($row['detail'] ? ' - ' . $row['detail'] : '')
                            : sprintf('%s - %s%s', $row['teacher'] ?? '-', $program, $row['detail'] ? ' ' . $row['detail'] : '');

                        return [
                            'label' => $label,
                            'count' => $row['count'],
                            'rate' => $row['rate'],
                            'total' => ($discount['base_total'] ?? $row['total']),
                            'penalty' => $row['penalty'],
                            'total_after' => $discountTotal,
                            'discount' => $discount,
                            'enrollment_id' => $row['enrollment_id'],
                            'student_id' => $student->id,
                            'type' => $row['type'],
                        ];
                    })->values();

                    return [
                        'student' => $student,
                        'lines' => $lines,
                        'total' => $result['grand_total'],
                        'total_before' => $result['grand_total'] + $result['total_discount'],
                    ];
                })->values();

                $grandTotal = $students->sum('total');

                $invoice = $invoices[(int) $parentId] ?? null;

                return [
                    'parent_id' => $parentId,
                    'parent_name' => $parentName,
                    'contact' => $contact,
                    'students' => $students,
                    'total' => $grandTotal,
                    'total_before' => $grandTotal + collect($students)->sum(fn ($s) => $s['lines']->sum(fn ($l) => $l['discount']['amount'] ?? 0)),
                    'message' => null,
                    'pdf_url' => $invoice
                        ? route('pdf.parent', [(int) $parentId, $invoice->filename])
                        : null,
                    'wa_sent' => $waLog && $waLog->sent_at !== null,
                    'wa_sent_at' => $waLog?->sent_at?->toIso8601String(),
                    'wa_sent_by' => $waLog?->sent_by ? User::find($waLog->sent_by)?->name : null,
                ];
            })
            ->values();

        // Generate PDF only for the selected parent (via ?selected=N)
        $selectedIndex = (int) $request->query('selected', 0);
        $selectedPdfUrl = null;

        if (isset($privatSummaries[$selectedIndex])) {
            $selectedParentId = (string) $privatSummaries[$selectedIndex]['parent_id'];
            $parent = $parents->get((int) $selectedParentId);
            $parentStudents = $parent?->students()->with('parent')->get();
            $studentIds = $privatSummaries[$selectedIndex]['students']->pluck('student.id')->toArray();
            $parentAttendances = $this->baseAttendanceQuery($month, $year)
                ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
                ->get();
            if ($parentStudents->isNotEmpty() && $parentAttendances->isNotEmpty()) {
                try {
                    $invoice = Invoice::where('parent_id', $parent->id)
                        ->where('month', $month)->where('year', $year)->first();
                    $pdfResult = app(\App\Services\Pdf\InvoiceService::class)
                        ->generateParentInvoice($parentStudents, $month, $year, $parentAttendances, $invoice?->filename ?: null);
                    $invoice ??= new Invoice(['parent_id' => $parent->id, 'month' => $month, 'year' => $year]);
                    $newBasename = basename($pdfResult['storage_path']);
                    if ($invoice->filename !== $newBasename) {
                        $invoice->filename = $newBasename;
                        $invoice->regenerated_at = now();
                        $invoice->save();
                    }
                    $selectedPdfUrl = route('pdf.parent', [$parent->id, $invoice->filename]);
                } catch (\Throwable) {
                    // PDF generation failed
                }
            }
        }

        // Update only the selected entry
        if (isset($privatSummaries[$selectedIndex])) {
            $selectedParentId = $privatSummaries[$selectedIndex]['parent_id'];
            $pdfUrl = $selectedPdfUrl;
            $msg = $this->buildPrivateParentMessage(
                $privatSummaries[$selectedIndex]['students'],
                $month,
                $year,
                $privatSummaries[$selectedIndex]['total'],
                $selectedPdfUrl
            );
            $privatSummaries = $privatSummaries
                ->map(function ($item) use ($selectedParentId, $pdfUrl, $msg) {
                    if ($item['parent_id'] === $selectedParentId) {
                        $item['pdf_url'] = $pdfUrl;
                        $item['message'] = $msg;
                    }
                    return $item;
                });
        }

        return view('admin.analysis.ortu', [
            'month' => $month,
            'year' => $year,
            'privatSummaries' => $privatSummaries,
        ]);
    }

    public function guru(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $enrolledAttendances = $this->baseAttendanceQuery($month, $year)->get();

        $teacherOnlyAttendances = MonthlyAttendance::with([
            'classSession.program', 'classSession.teachers', 'students',
        ])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('enrollment_id')
            ->get();

        $waLogs = WaNotificationLog::where('month', $month)
            ->where('year', $year)
            ->whereNotNull('teacher_id')
            ->get()
            ->keyBy('teacher_id');

        $allTeacherIds = collect()
            ->merge($enrolledAttendances->pluck('enrollment.teacher')->filter()->unique('id')->pluck('id'))
            ->merge($enrolledAttendances->pluck('sessionTeacher')->filter()->unique('id')->pluck('id'))
            ->merge($enrolledAttendances->pluck('classSession.teachers.*')->flatten(1)->filter()->unique('id')->pluck('id'))
            ->merge($teacherOnlyAttendances->pluck('session_teacher_id')->unique()->filter())
            ->unique()
            ->values();

        $teachersById = Teacher::whereIn('id', $allTeacherIds)->get()->keyBy('id');

        $salaries = Salary::where('month', $month)
            ->where('year', $year)
            ->whereIn('teacher_id', $allTeacherIds)
            ->get()
            ->keyBy('teacher_id');

        $summaries = [];

        foreach ($allTeacherIds as $teacherId) {
            $teacher = $teachersById->get($teacherId);
            if (!$teacher) {
                continue;
            }

            $waLog = $waLogs[$teacherId] ?? null;

            // Privat: enrollment where this teacher is the owner
            $teacherPrivatAttendances = $enrolledAttendances
                ->filter(fn (MonthlyAttendance $a) =>
                    $a->enrollment
                    && ! $a->enrollment->isKelas()
                    && (int) ($a->enrollment->teacher_id ?? 0) === $teacherId
                );

            // Kelas: records where session_teacher_id explicitly matches this teacher
            $teacherKelasAttendances = $enrolledAttendances
                ->filter(fn (MonthlyAttendance $a) =>
                    $a->enrollment
                    && $a->enrollment->isKelas()
                    && (int) ($a->session_teacher_id ?? 0) === $teacherId
                );

            $teacherOnlyForTeacher = $teacherOnlyAttendances
                ->filter(fn (MonthlyAttendance $a) => (int) ($a->session_teacher_id ?? 0) === $teacherId);

            $allTeacherAttendances = $teacherPrivatAttendances
                ->merge($teacherKelasAttendances)
                ->merge($teacherOnlyForTeacher);

            $result = $this->calculationService->calculateTeacherSalary($teacherId, $month, $year, $allTeacherAttendances);

            $allLines = collect($result['rows'])->map(function (array $row) {
                $type = $row['type'] ?? 'privat';
                $programName = $row['program']?->name ?? '-';

                // Privat: "StudentName (grup N orang) - ProgramName". Kelas: "ProgramName" only.
                $label = $type === 'privat'
                    ? sprintf('%s - %s', $row['student_label'] ?? ($row['student'] ?: '-'), $programName)
                    : $programName;

                return [
                    'label' => $label,
                    'count' => $row['count'],
                    'rate' => $row['rate'],
                    'total' => $row['total'],
                    'penalty' => $row['penalty'],
                    'net_total' => $row['total'] - $row['penalty'],
                    'late_count' => $row['late_count'],
                    'type' => $type,
                    'count_label' => $row['label_detail'] ?? '',
                ];
            })->values();

            $grandTotal = (int) $allLines->sum('total');
            $finalTotal = $result['final_total'];
            $latePenalty = $result['total_penalty'];
            $lateCountTotal = (int) $allLines->sum('late_count');

            $summaries[] = [
                'teacher' => $teacher,
                'lines' => $allLines,
                'total' => $finalTotal,
                'gross' => $grandTotal,
                'late_penalty' => $latePenalty,
                'late_count' => $lateCountTotal,
                'message' => null,
                'pdf_url' => isset($salaries[$teacherId])
                    ? route('pdf.guru', [$teacherId, $salaries[$teacherId]->filename])
                    : null,
                'wa_sent' => $waLog && $waLog->sent_at !== null,
                'wa_sent_at' => $waLog?->sent_at?->toIso8601String(),
                'wa_sent_by' => $waLog?->sent_by ? User::find($waLog->sent_by)?->name : null,
            ];
        }

        // Generate PDF only for the selected teacher (via ?selected=N)
        $selectedIndex = (int) $request->query('selected', 0);
        $selectedPdfUrl = null;

        if (isset($summaries[$selectedIndex])) {
            $selectedTeacher = $summaries[$selectedIndex]['teacher'];
            $selectedTeacherId = $selectedTeacher->id;
            $pdfAttendances = $enrolledAttendances
                ->filter(fn (MonthlyAttendance $a) =>
                    ($a->enrollment && ! $a->enrollment->isKelas() && (int) ($a->enrollment->teacher_id ?? 0) === $selectedTeacherId)
                    || ((int) ($a->session_teacher_id ?? 0) === $selectedTeacherId)
                );
            $pdfTeacherOnly = $teacherOnlyAttendances->filter(fn ($a) => $a->session_teacher_id === $selectedTeacherId);
            $allPdfAttendances = $pdfAttendances->merge($pdfTeacherOnly);
            if ($allPdfAttendances->isNotEmpty()) {
                try {
                    $salary = Salary::where('teacher_id', $selectedTeacher->id)
                        ->where('month', $month)->where('year', $year)->first();
                    $pdfResult = app(\App\Services\Pdf\InvoiceService::class)
                        ->generateTeacherSalarySlip($selectedTeacher, $month, $year, $allPdfAttendances, $salary?->filename ?: null);
                    $salary ??= new Salary(['teacher_id' => $selectedTeacher->id, 'month' => $month, 'year' => $year]);
                    $newBasename = basename($pdfResult['storage_path']);
                    if ($salary->filename !== $newBasename) {
                        $salary->filename = $newBasename;
                        $salary->regenerated_at = now();
                        $salary->save();
                    }
                    $selectedPdfUrl = route('pdf.guru', [$selectedTeacher->id, $salary->filename]);
                } catch (\Throwable) {
                    // PDF generation failed
                }
            }
        }

        // Update only the selected entry
        if (isset($summaries[$selectedIndex])) {
            $summaries[$selectedIndex]['pdf_url'] = $selectedPdfUrl;
            $summaries[$selectedIndex]['message'] = $this->buildTeacherMessage(
                $summaries[$selectedIndex]['teacher'],
                $summaries[$selectedIndex]['lines'],
                $month,
                $year,
                $summaries[$selectedIndex]['gross'],
                $summaries[$selectedIndex]['late_penalty'],
                $summaries[$selectedIndex]['late_count'],
                $summaries[$selectedIndex]['total'],
                $selectedPdfUrl
            );
        }

        return view('admin.analysis.guru', [
            'month' => $month,
            'year' => $year,
            'summaries' => $summaries,
        ]);
    }

    public function toggleParentWaNotification(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['required', 'integer', 'exists:parents,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'sent' => ['required', 'boolean'],
        ]);

        $log = WaNotificationLog::firstOrCreate([
            'parent_id' => $validated['parent_id'],
            'month' => $validated['month'],
            'year' => $validated['year'],
        ]);

        if ($validated['sent']) {
            $log->markSent(auth()->user(), $request->string('message_snapshot')->toString() ?: null);
        } else {
            $log->markUnsent();
        }

        return response()->json([
            'sent' => $log->sent_at !== null,
            'sent_at' => $log->sent_at?->toIso8601String(),
            'sent_by' => $log->sent_by ? User::find($log->sent_by)?->name : null,
        ]);
    }

    public function toggleTeacherWaNotification(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'sent' => ['required', 'boolean'],
        ]);

        $log = WaNotificationLog::firstOrCreate([
            'teacher_id' => $validated['teacher_id'],
            'month' => $validated['month'],
            'year' => $validated['year'],
        ]);

        if ($validated['sent']) {
            $log->markSent(auth()->user(), $request->string('message_snapshot')->toString() ?: null);
        } else {
            $log->markUnsent();
        }

        return response()->json([
            'sent' => $log->sent_at !== null,
            'sent_at' => $log->sent_at?->toIso8601String(),
            'sent_by' => $log->sent_by ? User::find($log->sent_by)?->name : null,
        ]);
    }

    public function paymentsOrtu(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $attendances = $this->baseAttendanceQuery($month, $year)->get();

        // Group all attendances by parent → student for efficient per-student billing
        $parentStudentAttendances = [];
        foreach ($attendances as $attendance) {
            foreach ($attendance->students as $student) {
                if (($student->pivot->total_present ?? 0) <= 0) {
                    continue;
                }
                $parent = $student->parent;
                $parentId = $parent?->id ?? 'unknown';
                $studentId = $student->id;
                $parentStudentAttendances[$parentId][$studentId]['student'] = $student;
                $parentStudentAttendances[$parentId][$studentId]['attendances'][] = $attendance;
            }
        }

        $proofs = PaymentProof::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('parent_id');

        // Preload parent_payment_status for all attendance IDs (single query)
        $allAttendanceIds = collect($parentStudentAttendances)
            ->flatMap(fn (array $studentsData) => collect($studentsData)
                ->flatMap(fn (array $data) => collect($data['attendances'])->pluck('id')))
            ->unique()
            ->values()
            ->all();
        $attendanceStatuses = MonthlyAttendance::whereIn('id', $allAttendanceIds)
            ->where('parent_payment_status', 'paid')
            ->pluck('id')
            ->toArray();
        $paidAttendanceIds = array_flip($attendanceStatuses);

        $parentIds = array_keys($parentStudentAttendances);
        $parents = ParentModel::whereIn('id', $parentIds)->get()->keyBy('id');

        $summaries = collect($parentStudentAttendances)
            ->map(function (array $studentsData, string $parentId) use ($month, $year, $parents, $proofs, $paidAttendanceIds) {
                $parent = $parents->get((int) $parentId);
                $parentName = $parent?->name ?? 'Unknown';
                $proof = $proofs->get((int) $parentId);

                $allAttendanceIds = [];
                $students = collect($studentsData)->map(function (array $data) use ($month, $year, &$allAttendanceIds) {
                    $student = $data['student'];
                    $studentAttendances = collect($data['attendances']);

                    $result = $this->calculationService->calculateStudentBilling($student, $month, $year, $studentAttendances);

                    $lines = collect($result['rows'])->map(function (array $row) use (&$allAttendanceIds) {
                        $allAttendanceIds = array_merge($allAttendanceIds, $row['attendance_ids'] ?? []);
                        $discount = $row['discount'] ?? [];
                        $discountAmount = $discount['amount'] ?? 0;

                        return [
                            'label' => $row['type'] === 'kelas'
                                ? $row['program'] . ($row['detail'] ? ' - ' . $row['detail'] : '')
                                : sprintf('%s - %s%s', $row['teacher'], $row['program'], $row['detail'] ? ' ' . $row['detail'] : ''),
                            'count' => $row['count'],
                            'rate' => $row['rate'],
                            'total' => ($discount['base_total'] ?? $row['total']),
                            'total_after' => $row['total'],
                            'discount_type' => $discount['type'] ?? null,
                            'discount_amount' => $discountAmount,
                            'discount_label' => $discountAmount > 0
                                ? sprintf('Diskon %s', $discount['label'] ?? number_format($discountAmount))
                                : null,
                            'payment_status' => null,
                            'attendance_id' => $row['attendance_ids'][0] ?? null,
                            'type' => $row['type'],
                        ];
                    });

                    return [
                        'student' => $student,
                        'lines' => $lines->values(),
                        'total' => $result['grand_total'],
                        'total_before' => $result['grand_total'] + $result['total_discount'],
                    ];
                })->values();

                return [
                    'parent_id' => (int) $parentId ?: null,
                    'parent_name' => $parentName,
                    'proof' => $proof,
                    'attendance_ids' => array_unique($allAttendanceIds),
                    'has_paid' => !empty(array_intersect_key(array_flip($allAttendanceIds), $paidAttendanceIds)),
                    'students' => $students,
                    'total' => $students->sum('total'),
                    'total_before' => $students->sum('total_before'),
                ];
            })
            ->values();

        // Filter by search term (moved from blade to controller for pagination support)
        $search = trim(strtolower($request->input('search', '') ?? ''));
        if ($search) {
            $summaries = $summaries->filter(function ($summary) use ($search) {
                if (str_contains(strtolower($summary['parent_name'] ?? ''), $search)) {
                    return true;
                }
                foreach ($summary['students'] as $st) {
                    if (str_contains(strtolower($st['student']?->display_name ?? ''), $search)) {
                        return true;
                    }
                }
                return false;
            })->values();
        }

        // Paginate the summaries collection
        $perPage = 20;
        $currentPage = (int) $request->input('page', 1);
        $total = $summaries->count();
        $paginated = new LengthAwarePaginator(
            $summaries->slice(($currentPage - 1) * $perPage, $perPage)->values(),
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.payments.ortu', [
            'month' => $month,
            'year' => $year,
            'summaries' => $paginated,
        ]);
    }

    public function paymentsGuru(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $enrolledAttendances = $this->baseAttendanceQuery($month, $year)->get();

        // Teacher-only records (enrollment_id=null, no students)
        $teacherOnlyAttendances = MonthlyAttendance::with(['classSession.program', 'sessionTeacher'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('enrollment_id')
            ->get();

        // Build one summary per teacher
        $allTeacherIds = collect()
            ->merge($enrolledAttendances->pluck('enrollment.teacher')->filter()->unique('id')->pluck('id'))
            ->merge($enrolledAttendances->pluck('sessionTeacher')->filter()->unique('id')->pluck('id'))
            ->merge($enrolledAttendances->pluck('classSession.teachers.*')->flatten(1)->filter()->unique('id')->pluck('id'))
            ->merge($teacherOnlyAttendances->pluck('session_teacher_id')->unique()->filter())
            ->unique()
            ->values();

        $teachersById = Teacher::whereIn('id', $allTeacherIds)->get()->keyBy('id');

        $summaries = $allTeacherIds
            ->map(function (int $teacherId) use ($month, $year, $enrolledAttendances, $teacherOnlyAttendances, $teachersById) {
                $teacher = $teachersById->get($teacherId);
                if (!$teacher) {
                    return null;
                }

                // Privat: enrollment where this teacher is the owner
                $teacherPrivatAttendances = $enrolledAttendances
                    ->filter(fn (MonthlyAttendance $a) =>
                        $a->enrollment
                        && ! $a->enrollment->isKelas()
                        && (int) ($a->enrollment->teacher_id ?? 0) === $teacherId
                    );

                // Kelas: records where session_teacher_id explicitly matches this teacher
                $teacherKelasAttendances = $enrolledAttendances
                    ->filter(fn (MonthlyAttendance $a) =>
                        $a->enrollment
                        && $a->enrollment->isKelas()
                        && (int) ($a->session_teacher_id ?? 0) === $teacherId
                    );

                $teacherOnlyForTeacher = $teacherOnlyAttendances
                    ->filter(fn (MonthlyAttendance $a) => (int) ($a->session_teacher_id ?? 0) === $teacherId);

                $allTeacherAttendances = $teacherPrivatAttendances
                    ->merge($teacherKelasAttendances)
                    ->merge($teacherOnlyForTeacher);

                $result = $this->calculationService->calculateTeacherSalary($teacherId, $month, $year, $allTeacherAttendances);

                $lines = collect($result['rows'])->map(function (array $row) {
                    $type = $row['type'] ?? 'privat';
                    $programName = $row['program']?->name ?? '-';
                    $label = $type === 'privat'
                        ? sprintf('%s - %s', $row['student_label'] ?? ($row['student'] ?: '-'), $programName)
                        : $programName;

                    return [
                        'label' => $label,
                        'count' => $row['count'],
                        'rate' => $row['rate'],
                        'total' => $row['total'],
                        'penalty' => $row['penalty'],
                        'late_count' => $row['late_count'],
                        'type' => $type,
                    ];
                })->values();

                $attendanceIds = collect($result['rows'])
                    ->flatMap(fn (array $row) => $row['attendance_ids'] ?? [])
                    ->unique()
                    ->values()
                    ->toArray();

                $anyPaid = count($attendanceIds) > 0
                    ? MonthlyAttendance::whereIn('id', $attendanceIds)->where('teacher_payment_status', 'paid')->exists()
                    : false;
                $anyHeld = count($attendanceIds) > 0
                    ? MonthlyAttendance::whereIn('id', $attendanceIds)->where('teacher_payment_status', 'held')->exists()
                    : false;
                $overallStatus = $anyPaid ? 'paid' : ($anyHeld ? 'held' : 'unpaid');

                return [
                    'teacher' => $teacher,
                    'lines' => $lines,
                    'total' => (int) $lines->sum('total'),
                    'penalty' => (int) $lines->sum('penalty'),
                    'net_total' => (int) $lines->sum('total') - (int) $lines->sum('penalty'),
                    'attendance_ids' => $attendanceIds,
                    'payment_status' => $overallStatus,
                ];
            })
            ->filter()
            ->values();

        // Filter by search term (moved from blade to controller for pagination support)
        $search = trim(strtolower($request->input('search', '') ?? ''));
        if ($search) {
            $summaries = $summaries->filter(function ($summary) use ($search) {
                return str_contains(strtolower($summary['teacher']?->displayName ?? ''), $search);
            })->values();
        }

        // Paginate the summaries collection
        $perPage = 20;
        $currentPage = (int) $request->input('page', 1);
        $total = $summaries->count();
        $paginated = new LengthAwarePaginator(
            $summaries->slice(($currentPage - 1) * $perPage, $perPage)->values(),
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.payments.guru', [
            'month' => $month,
            'year' => $year,
            'summaries' => $paginated,
        ]);
    }

    public function updateParentPayment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'parent_payment_status' => ['required', 'in:unpaid,paid'],
        ]);

        $attendance->update($validated);

        return $this->backWithQueryString('Status pembayaran ortu diperbarui.');
    }

    public function updateTeacherPayment(Request $request, MonthlyAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_payment_status' => ['required', 'in:unpaid,paid,held'],
        ]);

        $attendance->update($validated);

        return $this->backWithQueryString('Status gaji guru diperbarui.');
    }

    public function confirmParentPaymentProof(Request $request, PaymentProof $paymentProof): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        $reviewedBy = $request->user()?->id;

        if ($validated['action'] === 'approve') {
            $paymentProof->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewedBy,
            ]);

            // Also mark all related attendances as paid
            $studentIds = $paymentProof->parent?->students->pluck('id') ?? collect();
            MonthlyAttendance::whereIn('year', [$paymentProof->year])
                ->where('month', $paymentProof->month)
                ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
                ->whereIn('status_validation', ['terima', 'terlambat'])
                ->update(['parent_payment_status' => 'paid']);

            return $this->backWithQueryString('Bukti pembayaran disetujui. Status berubah menjadi LUNAS.');
        }

        $paymentProof->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy,
        ]);

        return $this->backWithQueryString('Bukti pembayaran ditolak. Silakan minta upload ulang.');
    }

    public function updateParentMonthlyPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_ids' => ['required', 'string'],
            'parent_payment_status' => ['required', 'in:unpaid,paid'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', $validated['attendance_ids'])));

        if (! empty($ids)) {
            MonthlyAttendance::whereIn('id', $ids)->update([
                'parent_payment_status' => $validated['parent_payment_status'],
            ]);
        }

        return $this->backWithQueryString('Status pembayaran berhasil diperbarui.');
    }

    public function updateTeacherMonthlyPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_ids' => ['required', 'string'],
            'teacher_payment_status' => ['required', 'in:unpaid,paid,held'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', $validated['attendance_ids'])));

        if (! empty($ids)) {
            MonthlyAttendance::whereIn('id', $ids)->update([
                'teacher_payment_status' => $validated['teacher_payment_status'],
            ]);
        }

        return $this->backWithQueryString('Status gaji berhasil diperbarui.');
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

            return $this->backWithQueryString('Diskon privat dihapus.');
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

        return $this->backWithQueryString('Diskon privat diperbarui.');
    }

    public function generateInvoice(Request $request, Student $student, int $month, int $year): RedirectResponse
    {
        $parent = $student->parent;
        if (! $parent) {
            return $this->backWithQueryString('Murid ini tidak memiliki orang tua.');
        }

        // Redirect non-admin users to complete-data if profile is incomplete (they opened the WA link)
        $isAdmin = $request->user()?->role?->value === 'admin';
        if (! $isAdmin) {
            $missingParentData = blank($parent->name) || blank($parent->address);
            $missingStudentData = $parent->students()->where(fn ($query) => $query
                ->where(fn ($q) => $q->whereNull('nickname')->orWhereRaw('TRIM(COALESCE(nickname, "")) = ""'))
                ->orWhere(fn ($q) => $q->whereNull('full_name')->orWhereRaw('TRIM(COALESCE(full_name, "")) = ""'))
                ->orWhere(fn ($q) => $q->whereNull('sekolah')->orWhereRaw('TRIM(COALESCE(sekolah, "")) = ""'))
                ->orWhere(fn ($q) => $q->whereNull('kelas')->orWhereRaw('TRIM(COALESCE(kelas, "")) = ""'))
            )->exists();

            if ($missingParentData || $missingStudentData) {
                return redirect()->route('parent.billing.complete-data', [
                    'redirect_to' => route('admin.analysis.generate-invoice', ['student' => $student->id, 'month' => $month, 'year' => $year]),
                ]);
            }
        }

        $students = $parent->students()->with('parent')->get();

        $attendances = $this->baseAttendanceQuery($month, $year)
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $students->pluck('id')))
            ->where(fn ($query) => $query->whereNull('parent_review_status')->orWhere('parent_review_status', '!=', 'pending'))
            ->get();

        // Also include teacher-only records (enrollment_id=null, students still billed)
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
            return $this->backWithQueryString('Tidak ada data absensi untuk murid-murid ini pada periode tersebut.');
        }

        $invoiceService = app(\App\Services\Pdf\InvoiceService::class);
        $invoice = Invoice::where('parent_id', $parent->id)
            ->where('month', $month)->where('year', $year)->first();
        $result = $invoiceService->generateParentInvoice(
            $students, $month, $year, $attendances, $invoice?->filename ?: null
        );
        $invoice ??= new Invoice(['parent_id' => $parent->id, 'month' => $month, 'year' => $year]);
        $newBasename = basename($result['storage_path']);
        if ($invoice->filename !== $newBasename) {
            $invoice->filename = $newBasename;
            $invoice->regenerated_at = now();
            $invoice->save();
        }
        $redirectUrl = route('pdf.parent', [$parent->id, $invoice->filename]);

        return redirect($redirectUrl);
    }

    public function generateSalary(Request $request, Teacher $teacher, int $month, int $year): RedirectResponse
    {
        // Redirect non-admin users to complete-data if profile is incomplete (they opened the WA link)
        $isAdmin = $request->user()?->role?->value === 'admin';
        if (! $isAdmin) {
            $hasMissingTeacherIdentity = blank($teacher->nickname) && blank($teacher->full_name);
            $hasMissingTeacherProfile = blank($teacher->major) || blank($teacher->subjects) || blank($teacher->address);

            if ($hasMissingTeacherIdentity || $hasMissingTeacherProfile) {
                return redirect()->route('guru.complete-data', [
                    'redirect_to' => route('admin.analysis.generate-salary', ['teacher' => $teacher->id, 'month' => $month, 'year' => $year]),
                ]);
            }
        }

        $attendances = $this->baseAttendanceQuery($month, $year)
            ->where(function ($query) use ($teacher) {
                // Privat: teacher owns the enrollment
                $query->whereHas('enrollment', fn ($q) => $q
                    ->where('teacher_id', $teacher->id));

                // Kelas: this specific attendance's session_teacher_id matches
                $query->orWhere(fn ($q) => $q
                    ->whereNotNull('class_session_id')
                    ->where('session_teacher_id', $teacher->id));
            })
            ->get();

        // Also include teacher-only records (enrollment_id=null, kelas tanpa murid)
        $teacherOnlyAttendances = MonthlyAttendance::with(['classSession.program', 'students'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('enrollment_id')
            ->where('session_teacher_id', $teacher->id)
            ->get();

        $attendances = $attendances->merge($teacherOnlyAttendances);

        if ($attendances->isEmpty()) {
            return $this->backWithQueryString('Tidak ada data absensi untuk guru ini pada periode tersebut.');
        }

        $invoiceService = app(InvoiceService::class);
        $salary = Salary::where('teacher_id', $teacher->id)
            ->where('month', $month)->where('year', $year)->first();
        $result = $invoiceService->generateTeacherSalarySlip(
            $teacher, $month, $year, $attendances, $salary?->filename ?: null
        );
        $salary ??= new Salary(['teacher_id' => $teacher->id, 'month' => $month, 'year' => $year]);
        $newBasename = basename($result['storage_path']);
        if ($salary->filename !== $newBasename) {
            $salary->filename = $newBasename;
            $salary->regenerated_at = now();
            $salary->save();
        }
        $redirectUrl = route('pdf.guru', [$teacher->id, $salary->filename]);

        return redirect($redirectUrl);
    }

    private function baseAttendanceQuery(int $month, int $year)
    {
        return MonthlyAttendance::query()
            ->with(['enrollment.program', 'enrollment.teacher', 'sessionTeacher', 'students', 'classSession.teachers'])
            ->whereIn('status_validation', ['terima', 'terlambat'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderByRaw('CAST(enrollment_id AS UNSIGNED)');
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
            $totalSessionsThisMonth = $monthlyEnrollmentSessions[$attendance->enrollment_id] ?? 0;

            if ($enrollment?->isKelas()) {
                // For kelas: use the specific session_teacher_id stored in this MonthlyAttendance record.
                // If session_teacher_id is set (new store/update logic), use that teacher only.
                // If not set (legacy data), fall back to iterating all teachers in the pivot.
                $classSession = $attendance->classSession;
                $teacherId = $attendance->session_teacher_id;

                if ($teacherId) {
                    // New multi-teacher logic: each enrollment is paired to one specific teacher
                    $teacher = $classSession
                        ? $classSession->teachers->firstWhere('id', $teacherId)
                        : Teacher::find($teacherId);
                    $pivotRate = (int) ($teacher?->pivot?->rate ?? $attendance->teacher_rate ?? 0);
                    $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();

                    return $attendance->students->map(function (Student $student) use ($attendance, $enrollment, $program, $teacher, $totalSessionsThisMonth, $monthlyStudentTotals, $pivotRate, $presentCount) {
                        $studentKey = $attendance->enrollment_id . '-' . $student->id;
                        $studentTotalPresent = $monthlyStudentTotals[$studentKey] ?? 0;
                        $parentRate = (int) ($attendance->parent_rate ?? $enrollment?->parent_rate ?? 0);

                        return [
                            'attendance' => $attendance,
                            'enrollment' => $enrollment,
                            'program' => $program,
                            'teacher' => $teacher,
                            'student' => $student,
                            'total_present' => (int) ($student->pivot?->total_present ?? 0),
                            'parent_rate' => $parentRate,
                            'teacher_rate' => $pivotRate,
                            'status_validation' => $attendance->status_validation,
                            'has_penalty' => $enrollment?->hasAttendancePenalty($totalSessionsThisMonth, $studentTotalPresent) ?? false,
                            'present_count' => $presentCount,
                        ];
                    });
                }

                // Legacy: no session_teacher_id set — fall back to original all-teachers iteration
                $teachers = $classSession ? $classSession->teachers->all() : [];
                $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
                if (empty($teachers)) {
                    $teacher = null;
                    return $attendance->students->map(function (Student $student) use ($attendance, $enrollment, $program, $teacher, $totalSessionsThisMonth, $monthlyStudentTotals, $presentCount) {
                        $studentKey = $attendance->enrollment_id . '-' . $student->id;
                        $studentTotalPresent = $monthlyStudentTotals[$studentKey] ?? 0;
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
                            'present_count' => $presentCount,
                        ];
                    });
                }

                return collect($teachers)->flatMap(function (Teacher $teacher) use ($attendance, $enrollment, $program, $totalSessionsThisMonth, $monthlyStudentTotals, $presentCount) {
                    $pivotRate = (int) ($teacher->pivot->rate ?? $attendance->teacher_rate ?? 0);
                    return $attendance->students->map(function (Student $student) use ($attendance, $enrollment, $program, $teacher, $totalSessionsThisMonth, $monthlyStudentTotals, $pivotRate, $presentCount) {
                        $studentKey = $attendance->enrollment_id . '-' . $student->id;
                        $studentTotalPresent = $monthlyStudentTotals[$studentKey] ?? 0;
                        $parentRate = (int) ($attendance->parent_rate ?? $enrollment?->parent_rate ?? 0);

                        return [
                            'attendance' => $attendance,
                            'enrollment' => $enrollment,
                            'program' => $program,
                            'teacher' => $teacher,
                            'student' => $student,
                            'total_present' => (int) ($student->pivot?->total_present ?? 0),
                            'parent_rate' => $parentRate,
                            'teacher_rate' => $pivotRate,
                            'status_validation' => $attendance->status_validation,
                            'has_penalty' => $enrollment?->hasAttendancePenalty($totalSessionsThisMonth, $studentTotalPresent) ?? false,
                            'present_count' => $presentCount,
                        ];
                    });
                });
            }

            // Privat: single teacher per enrollment — enrollment exists but isKelas() is false
            // This block is reached only when $enrollment is not null AND not kelas (i.e., privat)
            // Note: the isKelas() if-block above returns early, so we add privat here as an else case.
            if ($enrollment) {
                $teacher = $enrollment->teacher;
                $totalSessionsThisMonth = $monthlyEnrollmentSessions[$attendance->enrollment_id] ?? 0;

                return $attendance->students->map(function (Student $student) use ($attendance, $enrollment, $program, $teacher, $totalSessionsThisMonth, $monthlyStudentTotals) {
                    $presentCount = $attendance->students->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)->count();
                    $studentKey = $attendance->enrollment_id . '-' . $student->id;
                    $studentTotalPresent = $monthlyStudentTotals[$studentKey] ?? 0;

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
                        'present_count' => $presentCount,
                    ];
                });
            }

            // Teacher-only record: no enrollment, handled by guru() via $teacherOnlyAttendances
            return collect();
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

    private function buildPrivateParentMessage(Collection $students, int $month, int $year, int $grandTotal, ?string $pdfUrl = null): string
    {
        $lines = collect([
            'Selamat pagi Bapak/Ibu. Maaf menganggu waktunya.',
            '',
            sprintf('Total les sampai akhir *%s %s* dapat dilihat pada link berikut ini:', $this->monthName($month), $year),
            '',
        ]);

        if ($pdfUrl) {
            $lines->push(sprintf('PDF Tagihan: %s', $pdfUrl));
        }
        $lines->push('');
        $lines->push('*Apabila data belum lengkap, akan diarahkan untuk mengisi form terlebih dahulu, harap diisi dengan seksama untuk kelancaran administrasi Bimbel Gracia.*');
        $lines->push('');
        $lines->push('Mohon konfirmasi jika sudah transfer.');
        $lines->push('Jika ada kritik/saran untuk tentor/bimbel, atau ingin mengetahui perkembangan siswa, kami terbuka untuk berdiskusi lewat WhatsApp.');
        $lines->push('Terima kasih atas perhatiannya.');

        return $lines->implode("\n");
    }

    private function buildTeacherMessage(?Teacher $teacher, Collection $lines, int $month, int $year, int $grandTotal, int $latePenalty = 0, int $lateCountTotal = 0, int $finalTotal = 0, ?string $pdfUrl = null): string
    {
        $messageLines = collect([
            'Selamat pagi. Minta tolong dicek slip gaji berikut ini dan segera konfirmasi jika ada perubahan nomor rekening.',
            '',
        ]);

        if ($pdfUrl) {
            $messageLines->push(sprintf('Slip Gaji: %s', $pdfUrl));
        }
        $messageLines->push('');
        $messageLines->push('*Apabila data belum lengkap, akan diarahkan untuk mengisi form terlebih dahulu, harap diisi dengan seksama untuk kelancaran administrasi Bimbel Gracia.*');
        $messageLines->push('');
        $messageLines->push('Mohon konfirmasi jika slip sudah sesuai.');
        $messageLines->push('Terima kasih atas dedikasinya.');

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

}