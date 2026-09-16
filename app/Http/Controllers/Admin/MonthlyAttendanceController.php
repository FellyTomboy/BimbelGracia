<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function __construct(private AttendanceFineService $fineService) {}

    public function index(Request $request): View
    {
        $query = MonthlyAttendance::with([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ])
            // Validasi presensi hanya untuk privat — exclude dari presensi kelas
            ->whereNull('class_session_id');

        // Filter by validation status
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'pending') {
                $query->whereNull('status_validation');
            } else {
                $query->where('status_validation', $status);
            }
        }

        // Search across student names, program names, lesson date, and teacher names/nicknames
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lesson_date', 'like', "%{$search}%")
                    ->orWhereHas('enrollment', function ($eq) use ($search) {
                        $eq->whereHas('teacher', function ($tq) use ($search) {
                            $tq->where(function ($q2) use ($search) {
                                $q2->where('full_name', 'like', "%{$search}%")
                                    ->orWhere('nickname', 'like', "%{$search}%");
                            });
                        })
                        ->orWhereHas('program', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->orWhereHas('students', function ($sq) use ($search) {
                        $sq->where(function ($q2) use ($search) {
                            $q2->where('full_name', 'like', "%{$search}%")
                                ->orWhere('nickname', 'like', "%{$search}%");
                        });
                    });
            });
        }

        // Sort
        $sortField = $request->get('sort', 'lesson_date');
        $sortDir = $request->get('dir', 'desc');

        $allowedSorts = ['lesson_date', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'lesson_date';
        }
        if (! in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        // If sorting by related fields, join and order manually
        if ($sortField === 'lesson_date') {
            $query->orderBy('lesson_date', $sortDir);
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $attendances = $query->paginate(20)->withQueryString();

        $billingMode = $this->fineService->getBillingMode();

        return view('admin.presensi.index', compact('attendances', 'billingMode'));
    }

    public function createForm(Request $request): JsonResponse
    {
        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->where('status', 'active')
            ->whereHas('program', fn($q) => $q->where('type', 'privat'))
            ->orderBy('id')
            ->get()
            ->map(function ($e) {
                $teacher = $e->teacher;
                $teacherFn = trim((string) ($teacher->full_name ?? ''));
                $teacherNn = trim((string) ($teacher->nickname ?? ''));
                return [
                    'id' => $e->id,
                    'type' => $e->type,
                    'program' => $e->program ? ['id' => $e->program->id, 'name' => $e->program->name, 'type' => $e->program->type] : null,
                    'teacher' => $teacher ? [
                        'id' => $teacher->id,
                        'display_name' => $teacherFn ? ($teacherNn ? "{$teacherFn} ({$teacherNn})" : $teacherFn) : ($teacherNn ?: 'Tanpa nama'),
                    ] : null,
                    'students' => $e->students->map(function ($s) {
                        $fn = trim((string) ($s->full_name ?? ''));
                        $nn = trim((string) ($s->nickname ?? ''));
                        return [
                            'id' => $s->id,
                            'name' => $fn ? ($nn ? "{$fn} ({$nn})" : $fn) : ($nn ?: 'Tanpa nama'),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $html = view('admin.presensi._form', [
            'attendance' => null,
            'enrollments' => $enrollments,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Tambah Presensi',
        ]);
    }

    public function editForm(MonthlyAttendance $attendance): JsonResponse
    {
        $attendance->load(['enrollment.program', 'enrollment.teacher', 'students']);

        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get()
            ->map(function ($e) {
                $teacher = $e->teacher;
                $teacherFn = trim((string) ($teacher->full_name ?? ''));
                $teacherNn = trim((string) ($teacher->nickname ?? ''));
                return [
                    'id' => $e->id,
                    'type' => $e->type,
                    'program' => $e->program ? ['id' => $e->program->id, 'name' => $e->program->name, 'type' => $e->program->type] : null,
                    'teacher' => $teacher ? [
                        'id' => $teacher->id,
                        'display_name' => $teacherFn ? ($teacherNn ? "{$teacherFn} ({$teacherNn})" : $teacherFn) : ($teacherNn ?: 'Tanpa nama'),
                    ] : null,
                    'students' => $e->students->map(function ($s) {
                        $fn = trim((string) ($s->full_name ?? ''));
                        $nn = trim((string) ($s->nickname ?? ''));
                        return [
                            'id' => $s->id,
                            'name' => $fn ? ($nn ? "{$fn} ({$nn})" : $fn) : ($nn ?: 'Tanpa nama'),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $html = view('admin.presensi._form', [
            'attendance' => $attendance,
            'enrollments' => $enrollments,
        ])->render();

        return response()->json([
            'html' => $html,
            'title' => 'Edit Presensi — ' . $attendance->lesson_date->format('d/m/Y'),
        ]);
    }

    public function create(): View
    {
        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->where('status', 'active')
            ->whereHas('program', fn ($q) => $q->where('type', 'privat'))
            ->orderBy('id')
            ->get()
            ->map(function ($e) {
                $teacher = $e->teacher;
                $teacherFn = trim((string) ($teacher->full_name ?? ''));
                $teacherNn = trim((string) ($teacher->nickname ?? ''));
                return [
                    'id'       => $e->id,
                    'type'     => $e->type,
                    'program'  => $e->program ? ['id' => $e->program->id, 'name' => $e->program->name, 'type' => $e->program->type] : null,
                    'teacher'  => $teacher ? [
                        'id' => $teacher->id,
                        'display_name' => $teacherFn ? ($teacherNn ? "{$teacherFn} ({$teacherNn})" : $teacherFn) : ($teacherNn ?: 'Tanpa nama'),
                    ] : null,
                    'students' => $e->students->map(function ($s) {
                        $fn = trim((string) ($s->full_name ?? ''));
                        $nn = trim((string) ($s->nickname ?? ''));
                        return [
                            'id' => $s->id,
                            'name' => $fn ? ($nn ? "{$fn} ({$nn})" : $fn) : ($nn ?: 'Tanpa nama'),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $billingMode = $this->fineService->getBillingMode();

        return view('admin.presensi.create', compact('enrollments', 'billingMode'));
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'sessions'      => ['required', 'array', 'min:1', 'max:31'],
            'sessions.*.lesson_date' => ['required', 'date'],
            'sessions.*.student_ids' => ['nullable', 'array'],
            'sessions.*.student_ids.*' => ['integer', 'exists:students,id'],
            'sessions.*.notes' => ['nullable', 'string', 'max:1000'],
            'sessions.*.image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $enrollment = Enrollment::with(['students', 'program'])->findOrFail($validated['enrollment_id']);
        $isClassProgram = $enrollment->isKelas();

        $createdCount = 0;
        $errors = [];

        \DB::transaction(function () use ($validated, $enrollment, $request, $isClassProgram, &$createdCount, &$errors) {
            foreach ($validated['sessions'] as $idx => $session) {
                $lessonDate = Carbon::parse($session['lesson_date']);
                $dateKey = $lessonDate->format('Y-m-d');

                if (\DB::table('enrollment_attendances')
                    ->where('enrollment_id', $enrollment->id)
                    ->whereDate('lesson_date', $dateKey)
                    ->exists()) {
                    $errors[] = "Tab " . ($idx + 1) . ": Presensi untuk {$dateKey} sudah ada.";
                    continue;
                }

                $status = $this->fineService->isBillingModeMonthly()
                    ? 'terima'
                    : ($lessonDate->diffInDays(now(), false) <= 3 ? 'terima' : 'terlambat');

                $imagePath = null;
                if ($request->hasFile("sessions.{$idx}.image")) {
                    $file = $request->file("sessions.{$idx}.image");
                    $ext = $file->getClientOriginalExtension();
                    $imagePath = "photo/attendance/admin/{$enrollment->id}/{$dateKey}_{$idx}.{$ext}";
                    $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
                }

                $studentIds = $session['student_ids'] ?? [];

                if ($isClassProgram) {
                    MonthlyAttendance::create([
                        'enrollment_id'     => $enrollment->id,
                        'lesson_date'       => $lessonDate,
                        'month'             => $lessonDate->month,
                        'year'              => $lessonDate->year,
                        'notes'             => $session['notes'] ?? null,
                        'image'             => $imagePath,
                        'status_validation' => $status,
                        'parent_rate'       => $enrollment->parent_rate,
                        'teacher_rate'      => $enrollment->teacher_rate,
                        'created_by'        => $request->user()->id,
                    ]);
                    $enrollment->updateQuietly(['validation_status' => 1]);
                } else {
                    if (empty($studentIds)) {
                        $errors[] = "Tab " . ($idx + 1) . ": Pilih minimal 1 murid yang hadir.";
                        continue;
                    }
                    $presentCount = count($studentIds);
                    $attendance = MonthlyAttendance::create([
                        'enrollment_id'     => $enrollment->id,
                        'lesson_date'       => $lessonDate,
                        'month'             => $lessonDate->month,
                        'year'              => $lessonDate->year,
                        'notes'             => $session['notes'] ?? null,
                        'image'             => $imagePath,
                        'status_validation' => $status,
                        'parent_rate'       => $enrollment->getParentRateForCount($presentCount),
                        'teacher_rate'      => $enrollment->getTeacherRateForCount($presentCount),
                        'created_by'        => $request->user()->id,
                    ]);
                    $attendance->students()->sync(
                        collect($studentIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                    );
                    $enrollment->updateQuietly(['validation_status' => 1]);
                }
                $createdCount++;
            }
        });

        if (! empty($errors)) {
            return back()->withErrors(['sessions' => implode(' ', $errors)])->withInput();
        }

        $message = $createdCount === 1
            ? 'Presensi berhasil dicatat.'
            : "{$createdCount} presensi berhasil dicatat sekaligus.";

        return redirect()->route('admin.presensi.index')->with('status', $message);
    }

    public function show(MonthlyAttendance $attendance): View
    {
        $attendance->load([
            'enrollment.program',
            'enrollment.teacher',
            'students',
        ]);
        $isClassPlaceholder = $this->hasClassPlaceholderStudent($attendance->students);

        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->when($isClassPlaceholder, function ($query) {
                $query->where('type', 'kelas');
            })
            ->orderBy('id')
            ->get();

        return view('admin.presensi.show', compact('attendance', 'enrollments', 'isClassPlaceholder'));
    }

    public function updateEnrollment(Request $request, MonthlyAttendance $attendance): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
        ]);

        $attendance->load('students');
        $enrollment = Enrollment::with(['students', 'program'])->findOrFail($validated['enrollment_id']);

        if ($this->hasClassPlaceholderStudent($attendance->students) && ! $enrollment->isKelas()) {
            return back()->withErrors([
                'enrollment_id' => 'Presensi murid kelas bersama harus memakai program bertipe kelas.',
            ]);
        }

        $attendance->update([
            'enrollment_id' => $enrollment->id,
            'parent_rate' => $enrollment->parent_rate,
            'teacher_rate' => $enrollment->teacher_rate,
            'status_validation' => $enrollment->isKelas() ? 'terima' : 'pending',
            'validated_at' => null,
            'validated_by' => null,
        ]);

        $attendance->students()->sync(
            $enrollment->students
                ->mapWithKeys(fn ($student) => [$student->id => ['total_present' => 0]])
                ->all()
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Enrollment diperbarui.']);
        }

        return $this->backWithQueryString('Enrollment diperbarui.');
    }

    public function edit(MonthlyAttendance $attendance): View
    {
        $attendance->load(['enrollment.program', 'enrollment.teacher', 'students']);

        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get();

        return view('admin.presensi.edit', compact('attendance', 'enrollments'));
    }

    public function update(Request $request, MonthlyAttendance $attendance): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    // Allow soft-deleted enrollments — attendance may have been created
                    // when the enrollment was still active.
                    $enrollment = Enrollment::withTrashed()->find($value);
                    if (!$enrollment) {
                        $fail('Enrollment tidak ditemukan.');
                    }
                },
            ],
            'lesson_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [
                'integer',
                function ($attribute, $value, $fail) {
                    // Allow soft-deleted students — attendance may have been created
                    // when the student was still active.
                    $student = Student::withTrashed()->find($value);
                    if (!$student) {
                        $fail('Murid tidak ditemukan.');
                    }
                },
            ],
        ]);

        $newEnrollment = Enrollment::with(['students', 'program'])
            ->findOrFail($validated['enrollment_id']);

        $lessonDate = Carbon::parse($validated['lesson_date']);

        // Duplicate check using new enrollment_id
        $exists = \DB::table('enrollment_attendances')
            ->where('enrollment_id', $newEnrollment->id)
            ->whereDate('lesson_date', $lessonDate->toDateString())
            ->where('id', '!=', $attendance->id)
            ->exists();
        if ($exists) {
            return back()
                ->withErrors(['lesson_date' => 'Presensi untuk enrollment dan tanggal ini sudah ada.'])
                ->withInput();
        }

        // Monthly billing: always accept. Daily billing: >3 days = late.
        $status = $this->fineService->isBillingModeMonthly()
            ? 'terima'
            : ($lessonDate->diffInDays(now(), false) <= 3 ? 'terima' : 'terlambat');

        $isEnrollmentChanged = $attendance->enrollment_id !== $newEnrollment->id;

        $updateData = [
            'enrollment_id' => $newEnrollment->id,
            'lesson_date' => $lessonDate,
            'month' => $lessonDate->month,
            'year' => $lessonDate->year,
            'notes' => $validated['notes'] ?? null,
            'status_validation' => $status,
        ];

        // Recalculate rates if enrollment changed or student list may change
        if ($isEnrollmentChanged || array_key_exists('student_ids', $validated)) {
            $studentIds = $validated['student_ids'] ?? [];
            $presentCount = ! empty($studentIds) ? count($studentIds) : $attendance->students()->count();
            $updateData['parent_rate'] = $newEnrollment->getParentRateForCount($presentCount);
            $updateData['teacher_rate'] = $newEnrollment->getTeacherRateForCount($presentCount);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($attendance->image) {
                Storage::disk('public')->delete($attendance->image);
            }
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $imagePath = sprintf(
                'photo/attendance/admin/%d_%s.%s',
                $newEnrollment->id,
                $lessonDate->format('Y-m-d'),
                $extension
            );
            $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
            $updateData['image'] = $imagePath;
        }

        $attendance->update($updateData);

        // Student sync
        if ($isEnrollmentChanged) {
            $checkedIds = $validated['student_ids'] ?? [];
            $newStudentIds = $newEnrollment->students->pluck('id')->toArray();
            if (! empty($checkedIds)) {
                $syncIds = array_intersect($checkedIds, $newStudentIds);
            } else {
                $syncIds = $newStudentIds;
            }
            if (! empty($syncIds)) {
                $attendance->students()->sync(
                    collect($syncIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                );
            } else {
                $attendance->students()->detach();
            }
        } elseif (array_key_exists('student_ids', $validated)) {
            $studentIds = $validated['student_ids'] ?? [];
            if (! empty($studentIds)) {
                $attendance->students()->sync(
                    collect($studentIds)->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                );
            } else {
                $attendance->students()->detach();
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('admin.presensi.index')
            ->with('status', 'Presensi berhasil diperbarui.');
    }

    public function destroy(Request $request, MonthlyAttendance $attendance): JsonResponse|RedirectResponse
    {
        if ($attendance->image) {
            Storage::disk('public')->delete($attendance->image);
        }

        $attendance->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Presensi berhasil dihapus.']);
        }

        return redirect()
            ->route('admin.presensi.index')
            ->with('status', 'Presensi berhasil dihapus.');
    }

    private function hasClassPlaceholderStudent($students): bool
    {
        $placeholder = (string) config('bimbel.class_student_placeholder', 'Murid Kelas Bersama');
        $placeholder = trim($placeholder);
        if ($placeholder === '') {
            return false;
        }

        $needle = strtolower($placeholder);

        return $students->contains(function (Student $student) use ($needle): bool {
            return strtolower($student->display_name) === $needle;
        });
    }

    public function previewValidate(MonthlyAttendance $attendance): JsonResponse
    {
        $html = view('admin.presensi._validate-form', [
            'attendance' => $attendance,
        ])->render();
        return response()->json(['html' => $html, 'title' => 'Validasi Presensi']);
    }

    public function previewFixEnrollment(MonthlyAttendance $attendance): JsonResponse
    {
        $attendance->load(['enrollment.program', 'enrollment.teacher', 'students']);
        $isClassPlaceholder = $this->hasClassPlaceholderStudent($attendance->students);

        $enrollments = Enrollment::with(['program', 'teacher', 'students'])
            ->when($isClassPlaceholder, fn ($q) => $q->where('type', 'kelas'))
            ->orderBy('id')
            ->get();

        $html = view('admin.presensi._fix-enrollment-form', [
            'attendance' => $attendance,
            'enrollments' => $enrollments,
            'isClassPlaceholder' => $isClassPlaceholder,
        ])->render();
        return response()->json(['html' => $html, 'title' => 'Perbaiki Enrollment']);
    }

    public function previewDelete(MonthlyAttendance $attendance): JsonResponse
    {
        $attendance->load(['enrollment.program', 'enrollment.teacher', 'students']);
        $html = view('admin.presensi._delete-confirm', [
            'attendance' => $attendance,
        ])->render();
        return response()->json(['html' => $html, 'title' => 'Hapus Presensi?']);
    }

    public function validateAttendance(Request $request, MonthlyAttendance $attendance): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:terima,terlambat,ditolak'],
        ]);

        $attendance->update([
            'status_validation' => $validated['status'],
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        if ($attendance->enrollment && in_array($validated['status'], ['terima', 'terlambat'])) {
            $attendance->enrollment->update(['validation_status' => 1]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Presensi divalidasi.']);
        }

        return redirect()
            ->route('admin.presensi.index')
            ->with('status', 'Presensi divalidasi.');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $enrollment = Enrollment::withTrashed()->find($value);
                    if (!$enrollment) {
                        $fail('Enrollment tidak ditemukan.');
                    }
                },
            ],
            'lesson_date' => ['required', 'date', 'before_or_equal:today'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [
                'integer',
                function ($attribute, $value, $fail) {
                    $student = Student::withTrashed()->find($value);
                    if (!$student) {
                        $fail('Murid tidak ditemukan.');
                    }
                },
            ],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $enrollment = Enrollment::with(['students', 'program'])
            ->findOrFail($validated['enrollment_id']);

        $isClassProgram = $enrollment->isKelas();

        // Duplicate check
        $exists = \DB::table('enrollment_attendances')
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('lesson_date', Carbon::parse($validated['lesson_date'])->toDateString())
            ->exists();
        if ($exists) {
            return redirect()
                ->route('admin.presensi.index')
                ->withErrors(['lesson_date' => 'Presensi untuk enrollment dan tanggal ini sudah ada.'])
                ->withInput();
        }

        $lessonDate = Carbon::parse($validated['lesson_date']);

        // Monthly billing: always accept. Daily billing: >3 days = late.
        $status = $this->fineService->isBillingModeMonthly()
            ? 'terima'
            : ($lessonDate->diffInDays(now(), false) <= 3 ? 'terima' : 'terlambat');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $imagePath = sprintf(
                'photo/attendance/admin/%d_%s.%s',
                $enrollment->id,
                $lessonDate->format('Y-m-d'),
                $extension
            );
            $file->storeAs(dirname($imagePath), basename($imagePath), 'public');
        }

        $attendance = MonthlyAttendance::create([
            'enrollment_id' => $enrollment->id,
            'lesson_date' => $lessonDate,
            'month' => $lessonDate->month,
            'year' => $lessonDate->year,
            'notes' => $validated['notes'] ?? null,
            'image' => $imagePath,
            'status_validation' => $status,
            'parent_rate' => $enrollment->parent_rate,
            'teacher_rate' => $enrollment->teacher_rate,
            'created_by' => $request->user()->id,
        ]);

        if ($isClassProgram) {
            // Attach placeholder so it shows in the list
            $enrollment->update(['validation_status' => 1]);
        } else {
            if (! empty($validated['student_ids'])) {
                $attendance->students()->sync(
                    collect($validated['student_ids'])->mapWithKeys(fn ($id) => [$id => ['total_present' => 1]])
                );
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi berhasil ditambahkan.',
                'attendance_id' => $attendance->id,
            ]);
        }

        return redirect()
            ->route('admin.presensi.index')
            ->with('status', 'Presensi berhasil ditambahkan.');
    }
}
