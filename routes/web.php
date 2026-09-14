<?php

use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\ClassReportController;
use App\Http\Controllers\Admin\ClassStudentSessionController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\AttendanceReviewController;
use App\Http\Controllers\Admin\ClassAttendanceController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\NewStudentController;
use App\Http\Controllers\Admin\TeacherRegistrantController;
use App\Http\Controllers\RegisterTeacherController;
use App\Http\Controllers\Admin\LessonOfferController as AdminLessonOfferController;
use App\Http\Controllers\Admin\MonthlyAttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Guru\DocumentController as GuruDocumentController;
use App\Http\Controllers\Guru\LessonOfferController as GuruLessonOfferController;
use App\Http\Controllers\Guru\MonthlyAttendanceController as GuruAttendanceController;
use App\Http\Controllers\Guru\HistoryController as GuruHistoryController;
use App\Http\Controllers\Guru\SalaryProjectionController as GuruSalaryProjectionController;
use App\Http\Controllers\Parent\BillingController as ParentBillingController;
use App\Http\Controllers\Parent\HistoryController as ParentHistoryController;
use App\Http\Controllers\PasswordForceController;
use App\Http\Controllers\RegisterStudentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::middleware(['auth', 'password.force'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard')->middleware('account.active');

    Route::prefix('admin')
        ->middleware('role:admin')
        ->name('admin.')
        ->group(function () {
            Route::get('students', [StudentController::class, 'index'])
                ->name('students.index');
            Route::get('students/inactive', [StudentController::class, 'inactive'])
                ->name('students.inactive');
            Route::any('students/bulk-delete', [StudentController::class, 'bulkDestroy'])
                ->name('students.bulk-destroy');
            Route::delete('students/{student}', [StudentController::class, 'destroy'])
                ->name('students.destroy');
            Route::post('students/{studentId}/restore', [StudentController::class, 'restore'])
                ->name('students.restore');
            Route::post('students/{id}/force-destroy', [StudentController::class, 'forceDestroy'])
                ->name('students.force-destroy');
            Route::post('students/bulk-force-destroy', [StudentController::class, 'bulkForceDestroy'])
                ->name('students.bulk-force-destroy');

            Route::resource('teachers', TeacherController::class)->except(['show']);
            Route::get('teachers/inactive', [TeacherController::class, 'inactive'])
                ->name('teachers.inactive');
            Route::post('teachers/{teacherId}/restore', [TeacherController::class, 'restore'])
                ->name('teachers.restore');
            Route::post('teachers/bulk-destroy', [TeacherController::class, 'bulkDestroy'])
                ->name('teachers.bulk-destroy');
            Route::post('teachers/{id}/force-destroy', [TeacherController::class, 'forceDestroy'])
                ->name('teachers.force-destroy');
            Route::post('teachers/bulk-force-destroy', [TeacherController::class, 'bulkForceDestroy'])
                ->name('teachers.bulk-force-destroy');
            Route::get('teachers/create-form', [TeacherController::class, 'createForm'])
                ->name('teachers.create-form');
            Route::get('teachers/{teacher}/form', [TeacherController::class, 'editForm'])
                ->name('teachers.edit-form');

            Route::post('programs/bulk-destroy', [ProgramController::class, 'bulkDestroy'])
                ->name('programs.bulk-destroy');
            Route::resource('programs', ProgramController::class)->except(['show']);
            Route::get('programs/inactive', [ProgramController::class, 'inactive'])
                ->name('programs.inactive');
            Route::post('programs/{program}/restore', [ProgramController::class, 'restore'])
                ->name('programs.restore');
            Route::post('programs/bulk-force-destroy', [ProgramController::class, 'bulkForceDestroy'])
                ->name('programs.bulk-force-destroy');
            Route::post('programs/{id}/force-destroy', [ProgramController::class, 'forceDestroy'])
                ->name('programs.force-destroy');
            Route::get('programs/form', [ProgramController::class, 'createForm'])
                ->name('programs.create-form');
            Route::get('programs/{program}/form', [ProgramController::class, 'editForm'])
                ->name('programs.edit-form');

            Route::resource('enrollments', EnrollmentController::class)->except(['show']);
            Route::get('enrollments/inactive', [EnrollmentController::class, 'inactive'])
                ->name('enrollments.inactive');
            Route::get('enrollments/mismatches', [EnrollmentController::class, 'mismatches'])
                ->name('enrollments.mismatches');
            Route::post('enrollments/{enrollment}/restore', [EnrollmentController::class, 'restore'])
                ->name('enrollments.restore');
            Route::post('enrollments/bulk-destroy', [EnrollmentController::class, 'bulkDestroy'])
                ->name('enrollments.bulk-destroy');
            Route::post('enrollments/bulk-force-destroy', [EnrollmentController::class, 'bulkForceDestroy'])
                ->name('enrollments.bulk-force-destroy');
            Route::post('enrollments/{id}/force-destroy', [EnrollmentController::class, 'forceDestroy'])
                ->name('enrollments.force-destroy');
            Route::get('enrollments/create-form', [EnrollmentController::class, 'createForm'])
                ->name('enrollments.create-form');
            Route::get('enrollments/{enrollment}/edit-form', [EnrollmentController::class, 'editForm'])
                ->name('enrollments.edit-form');

            Route::resource('lesson-offers', AdminLessonOfferController::class)->except(['show']);
            Route::get('lesson-offers/form', [AdminLessonOfferController::class, 'createForm'])
                ->name('lesson-offers.create-form');
            Route::get('lesson-offers/{lessonOffer}/form', [AdminLessonOfferController::class, 'editForm'])
                ->name('lesson-offers.edit-form');
            Route::get('lesson-offers/inactive', [AdminLessonOfferController::class, 'inactive'])
                ->name('lesson-offers.inactive');
            Route::post('lesson-offers/{lessonOffer}/restore', [AdminLessonOfferController::class, 'restore'])
                ->name('lesson-offers.restore');
            Route::post('lesson-offers/bulk-destroy', [AdminLessonOfferController::class, 'bulkDestroy'])
                ->name('lesson-offers.bulk-destroy');
            Route::post('lesson-offers/bulk-force-destroy', [AdminLessonOfferController::class, 'bulkForceDestroy'])
                ->name('lesson-offers.bulk-force-destroy');
            Route::post('lesson-offers/{id}/force-destroy', [AdminLessonOfferController::class, 'forceDestroy'])
                ->name('lesson-offers.force-destroy');

            Route::resource('bank-accounts', BankAccountController::class)->except(['show']);
            Route::get('bank-accounts/inactive', [BankAccountController::class, 'inactive'])
                ->name('bank-accounts.inactive');
            Route::post('bank-accounts/{bankAccount}/restore', [BankAccountController::class, 'restore'])
                ->name('bank-accounts.restore');
            Route::post('bank-accounts/bulk-destroy', [BankAccountController::class, 'bulkDestroy'])
                ->name('bank-accounts.bulk-destroy');
            Route::post('bank-accounts/bulk-force-destroy', [BankAccountController::class, 'bulkForceDestroy'])
                ->name('bank-accounts.bulk-force-destroy');
            Route::post('bank-accounts/{id}/force-destroy', [BankAccountController::class, 'forceDestroy'])
                ->name('bank-accounts.force-destroy');

            Route::resource('parents', ParentController::class)->except(['show']);
            Route::get('parents/inactive', [ParentController::class, 'inactive'])
                ->name('parents.inactive');
            Route::post('parents/{parent}/hibernate', [ParentController::class, 'hibernate'])
                ->name('parents.hibernate');
            Route::post('parents/{parent}/restore', [ParentController::class, 'restore'])
                ->name('parents.restore');
            Route::post('parents/bulk-destroy', [ParentController::class, 'bulkDestroy'])
                ->name('parents.bulk-destroy');
            Route::post('parents/bulk-restore', [ParentController::class, 'bulkRestore'])
                ->name('parents.bulk-restore');
            Route::post('parents/bulk-force-destroy', [ParentController::class, 'bulkForceDestroy'])
                ->name('parents.bulk-force-destroy');
            Route::post('parents/{id}/force-destroy', [ParentController::class, 'forceDestroy'])
                ->name('parents.force-destroy');
            Route::delete('parents/{parent}/students/{student}', [ParentController::class, 'removeStudent'])
                ->name('parents.remove-student');
            Route::post('parents/{parent}/add-student', [ParentController::class, 'addStudent'])
                ->name('parents.add-student');
            Route::put('parents/{parent}/students/{student}', [ParentController::class, 'updateStudent'])
                ->name('parents.update-student');
            Route::post('parents/{parent}/change-password', [ParentController::class, 'changePassword'])
                ->name('parents.change-password');
            Route::get('parents/create-form', [ParentController::class, 'createForm'])
                ->name('parents.create-form');
            Route::get('parents/{parent}/edit-form', [ParentController::class, 'editForm'])
                ->name('parents.edit-form');
            Route::get('parents/{parent}/students-json', [ParentController::class, 'studentsJson'])
                ->name('parents.students-json');
            Route::post('teachers/{teacher}/approve-photo', [TeacherController::class, 'approvePhoto'])
                ->name('teachers.approve-photo');
            Route::post('teachers/{teacher}/change-password', [TeacherController::class, 'changePassword'])
                ->name('teachers.change-password');

            Route::get('class-student-sessions', [ClassStudentSessionController::class, 'index'])
                ->name('class-student-sessions.index');
            Route::get('class-student-sessions/table', [ClassStudentSessionController::class, 'table'])
                ->name('class-student-sessions.table');

            Route::get('presensi', [AdminAttendanceController::class, 'index'])
                ->name('presensi.index');
            Route::get('presensi/create', [AdminAttendanceController::class, 'create'])
                ->name('presensi.create');
            Route::post('presensi/bulk', [AdminAttendanceController::class, 'storeBulk'])
                ->name('presensi.store-bulk');
            Route::post('presensi', [AdminAttendanceController::class, 'store'])
                ->name('presensi.store');
            Route::get('presensi/{attendance}', [AdminAttendanceController::class, 'show'])
                ->name('presensi.show');
            Route::get('presensi/{attendance}/edit', [AdminAttendanceController::class, 'edit'])
                ->name('presensi.edit');
            Route::put('presensi/{attendance}', [AdminAttendanceController::class, 'update'])
                ->name('presensi.update');
            Route::delete('presensi/{attendance}', [AdminAttendanceController::class, 'destroy'])
                ->name('presensi.destroy');
            Route::post('presensi/{attendance}/enrollment', [AdminAttendanceController::class, 'updateEnrollment'])
                ->name('presensi.enrollment');
            Route::post('presensi/{attendance}/validate', [AdminAttendanceController::class, 'validateAttendance'])
                ->name('presensi.validate');
            Route::get('notifikasi-presensi', [AttendanceReviewController::class, 'index'])
                ->name('notifications.index');
            Route::post('notifikasi-presensi/{attendance}/confirm', [AttendanceReviewController::class, 'upholdParentRejection'])
                ->name('notifications.uphold-rejection');
            Route::post('notifikasi-presensi/{attendance}/dismiss', [AttendanceReviewController::class, 'dismiss'])
                ->name('notifications.dismiss');

            Route::get('analysis/ortu', [AnalysisController::class, 'ortu'])
                ->name('analysis.ortu');
            Route::post('analysis/ortu/discount', [AnalysisController::class, 'updateEnrollmentDiscount'])
                ->name('analysis.ortu-discount');
            Route::patch('analysis/ortu/wa-notification', [AnalysisController::class, 'toggleParentWaNotification'])
                ->name('analysis.ortu.wa-notification');
            Route::get('analysis/guru', [AnalysisController::class, 'guru'])
                ->name('analysis.guru');
            Route::patch('analysis/guru/wa-notification', [AnalysisController::class, 'toggleTeacherWaNotification'])
                ->name('analysis.guru.wa-notification');
            Route::get('payments/ortu', [AnalysisController::class, 'paymentsOrtu'])
                ->name('payments.ortu');
            Route::get('payments/guru', [AnalysisController::class, 'paymentsGuru'])
                ->name('payments.guru');
            Route::post('payments/ortu/{attendance}/payment', [AnalysisController::class, 'updateParentPayment'])
                ->name('payments.ortu.payment');
            Route::post('payments/guru/{attendance}/payment', [AnalysisController::class, 'updateTeacherPayment'])
                ->name('payments.guru.payment');
            Route::post('payments/{attendance}/confirm-proof', [AnalysisController::class, 'confirmParentPaymentProof'])
                ->name('payments.confirm-proof');
            Route::post('payments/proof/{paymentProof}/confirm', [AnalysisController::class, 'confirmParentPaymentProof'])
                ->name('payments.ortu.confirm-proof');
            Route::post('payments/ortu/monthly-payment', [AnalysisController::class, 'updateParentMonthlyPayment'])
                ->name('payments.ortu.monthly-payment');
            Route::post('payments/guru/monthly-payment', [AnalysisController::class, 'updateTeacherMonthlyPayment'])
                ->name('payments.guru.monthly-payment');
            Route::get('payments/ortu-summary', [FinanceController::class, 'ortuSummary'])
                ->name('payments.ortu-summary');
            Route::get('payments/guru-summary', [FinanceController::class, 'guruSummary'])
                ->name('payments.guru-summary');
            Route::patch('payments/ortu/status', [FinanceController::class, 'updateOrtuPaymentStatus'])
                ->name('payments.ortu.status');
            Route::patch('payments/guru/status', [FinanceController::class, 'updateGuruPaymentStatus'])
                ->name('payments.guru.status');
            Route::post('analysis/generate-invoice/{student}/{month}/{year}', [AnalysisController::class, 'generateInvoice'])
                ->name('analysis.generate-invoice');
            Route::post('analysis/generate-salary/{teacher}/{month}/{year}', [AnalysisController::class, 'generateSalary'])
                ->name('analysis.generate-salary');

            Route::get('new-students', [NewStudentController::class, 'index'])
                ->name('new-students.index');
            Route::post('new-students/{newStudent}/convert', [NewStudentController::class, 'convert'])
                ->name('new-students.convert');
            Route::get('new-students/{newStudent}/preview-convert', [NewStudentController::class, 'previewConvert'])
                ->name('new-students.preview-convert');
            Route::get('new-students/{newStudent}/preview-delete', [NewStudentController::class, 'previewDelete'])
                ->name('new-students.preview-delete');
            Route::get('new-students/preview-delete-all', [NewStudentController::class, 'previewDeleteAll'])
                ->name('new-students.preview-delete-all');
            Route::delete('new-students/{newStudent}', [NewStudentController::class, 'destroy'])
                ->name('new-students.destroy');
            Route::delete('new-students/all', [NewStudentController::class, 'destroyAll'])
                ->name('new-students.destroy-all');

            Route::get('teacher-registrants', [TeacherRegistrantController::class, 'index'])
                ->name('teacher-registrants.index');
            Route::post('teacher-registrants/{teacherRegistrant}/convert', [TeacherRegistrantController::class, 'convert'])
                ->name('teacher-registrants.convert');
            Route::get('teacher-registrants/{teacherRegistrant}/preview-convert', [TeacherRegistrantController::class, 'previewConvert'])
                ->name('teacher-registrants.preview-convert');
            Route::get('teacher-registrants/{teacherRegistrant}/preview-delete', [TeacherRegistrantController::class, 'previewDelete'])
                ->name('teacher-registrants.preview-delete');
            Route::get('teacher-registrants/preview-delete-all', [TeacherRegistrantController::class, 'previewDeleteAll'])
                ->name('teacher-registrants.preview-delete-all');
            Route::delete('teacher-registrants/{teacherRegistrant}', [TeacherRegistrantController::class, 'destroy'])
                ->name('teacher-registrants.destroy');
            Route::delete('teacher-registrants/all', [TeacherRegistrantController::class, 'destroyAll'])
                ->name('teacher-registrants.destroy-all');

            Route::resource('documents', AdminDocumentController::class)->except(['show']);
            Route::get('documents/form', [AdminDocumentController::class, 'createForm'])
                ->name('documents.create-form');
            Route::get('documents/{document}/form', [AdminDocumentController::class, 'editForm'])
                ->name('documents.edit-form');
            Route::get('documents/{document}/download', [AdminDocumentController::class, 'download'])
                ->name('documents.download');
            Route::get('documents/{document}/preview', [AdminDocumentController::class, 'preview'])
                ->name('documents.preview');

            Route::get('discounts', [DiscountController::class, 'index'])
                ->name('discounts.index');
            Route::post('discounts', [DiscountController::class, 'store'])
                ->name('discounts.store');
            Route::post('discounts/preview', [DiscountController::class, 'previewForm'])
                ->name('discounts.preview');

            Route::get('finance', [FinanceController::class, 'index'])
                ->name('finance.index');

            Route::post('finance/snapshot/students', [FinanceController::class, 'snapshotStudents'])
                ->name('finance.snapshot.students');
            Route::post('finance/snapshot/teachers', [FinanceController::class, 'snapshotTeachers'])
                ->name('finance.snapshot.teachers');

            Route::get('class-attendance', [ClassAttendanceController::class, 'index'])
                ->name('class-attendance.index');
            Route::get('class-attendance/{attendance}/edit', [ClassAttendanceController::class, 'edit'])
                ->name('class-attendance.edit');
            Route::put('class-attendance/{attendance}', [ClassAttendanceController::class, 'update'])
                ->name('class-attendance.update');

            Route::get('class-student-sessions', [ClassStudentSessionController::class, 'index'])
                ->name('class-student-sessions.index');
            Route::get('class-student-sessions/table', [ClassStudentSessionController::class, 'table'])
                ->name('class-student-sessions.table');
            Route::get('class-student-sessions/create', [ClassStudentSessionController::class, 'create'])
                ->name('class-student-sessions.create');
            Route::post('class-student-sessions', [ClassStudentSessionController::class, 'store'])
                ->name('class-student-sessions.store');
            Route::get('class-student-sessions/{session}/edit', [ClassStudentSessionController::class, 'edit'])
                ->name('class-student-sessions.edit');
            Route::put('class-student-sessions/{session}', [ClassStudentSessionController::class, 'update'])
                ->name('class-student-sessions.update');
            Route::delete('class-student-sessions/{session}', [ClassStudentSessionController::class, 'destroy'])
                ->name('class-student-sessions.destroy');

            Route::get('class-reports', [ClassReportController::class, 'index'])
                ->name('class-reports.index');

            Route::get('history/students', [HistoryController::class, 'students'])
                ->name('history.students');
            Route::get('history/teachers', [HistoryController::class, 'teachers'])
                ->name('history.teachers');
            Route::get('history/payments', [HistoryController::class, 'payments'])
                ->name('history.payments');
            Route::get('history/audit', [HistoryController::class, 'audit'])
                ->name('history.audit');

            Route::get('export', [ExportController::class, 'index'])
                ->name('export.index');
            Route::get('export/students', [ExportController::class, 'students'])
                ->name('export.students');
            Route::get('export/students/excel', [ExportController::class, 'studentsExcel'])
                ->name('export.students.excel');
            Route::get('export/students/pdf', [ExportController::class, 'studentsPdf'])
                ->name('export.students.pdf');
            Route::get('export/teachers', [ExportController::class, 'teachers'])
                ->name('export.teachers');
            Route::get('export/teachers/excel', [ExportController::class, 'teachersExcel'])
                ->name('export.teachers.excel');
            Route::get('export/teachers/pdf', [ExportController::class, 'teachersPdf'])
                ->name('export.teachers.pdf');
            Route::get('export/lessons', [ExportController::class, 'lessons'])
                ->name('export.lessons');
            Route::get('export/lessons/excel', [ExportController::class, 'lessonsExcel'])
                ->name('export.lessons.excel');
            Route::get('export/lessons/pdf', [ExportController::class, 'lessonsPdf'])
                ->name('export.lessons.pdf');
            Route::get('export/attendances', [ExportController::class, 'attendances'])
                ->name('export.attendances');
            Route::get('export/attendances/excel', [ExportController::class, 'attendancesExcel'])
                ->name('export.attendances.excel');
            Route::get('export/attendances/pdf', [ExportController::class, 'attendancesPdf'])
                ->name('export.attendances.pdf');
            Route::get('export/attendances/monthly/excel', [ExportController::class, 'attendancesMonthlyExcel'])
                ->name('export.attendances.monthly.excel');
            Route::get('export/attendances/monthly/pdf', [ExportController::class, 'attendancesMonthlyPdf'])
                ->name('export.attendances.monthly.pdf');
            Route::get('export/class/excel', [ExportController::class, 'classExcel'])
                ->name('export.class.excel');
            Route::get('export/class/pdf', [ExportController::class, 'classPdf'])
                ->name('export.class.pdf');
            Route::get('export/audit', [ExportController::class, 'auditLogs'])
                ->name('export.audit');
            Route::get('export/audit/excel', [ExportController::class, 'auditLogsExcel'])
                ->name('export.audit.excel');
            Route::get('export/audit/pdf', [ExportController::class, 'auditLogsPdf'])
                ->name('export.audit.pdf');
            Route::post('export/backup', [ExportController::class, 'backupDatabase'])
                ->name('export.backup');
        });

    Route::get('/guru', function () {
        return view('guru.dashboard');
    })->middleware(['role:guru', 'account.active'])->name('guru.dashboard');

    Route::middleware(['role:guru', 'account.active'])->prefix('guru')->name('guru.')->group(function () {
        Route::get('presensi', [GuruAttendanceController::class, 'index'])->name('presensi.index');
        Route::get('presensi/create', [GuruAttendanceController::class, 'create'])->name('presensi.create');
        Route::post('presensi', [GuruAttendanceController::class, 'store'])->name('presensi.store');
        Route::post('presensi/bulk', [GuruAttendanceController::class, 'storeBulk'])->name('presensi.store-bulk');
        Route::get('presensi/{attendance}/edit', [GuruAttendanceController::class, 'edit'])->name('presensi.edit');
        Route::put('presensi/{attendance}', [GuruAttendanceController::class, 'update'])->name('presensi.update');
        Route::delete('presensi/{attendance}', [GuruAttendanceController::class, 'destroy'])->name('presensi.destroy');
        Route::get('tawaran', [GuruLessonOfferController::class, 'index'])->name('tawaran.index');
        Route::get('riwayat', [GuruHistoryController::class, 'index'])->name('history.index');
        Route::get('proyeksi-gaji', [GuruSalaryProjectionController::class, 'index'])->name('salary-projection.index');
        Route::get('complete-data', [\App\Http\Controllers\Admin\TeacherController::class, 'completeData'])->name('complete-data');
        Route::post('complete-data', [\App\Http\Controllers\Admin\TeacherController::class, 'submitCompleteData'])->name('complete-data.store');
        Route::get('documents', [GuruDocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [GuruDocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}/verify-password', [GuruDocumentController::class, 'verifyPassword'])->name('documents.verify-password');
        Route::get('documents/{document}/viewer', [GuruDocumentController::class, 'viewer'])->name('documents.viewer');
        Route::get('documents/{document}/view', [GuruDocumentController::class, 'view'])->name('documents.view');
        Route::get('documents/{document}/download', [GuruDocumentController::class, 'download'])->name('documents.download');
    });

    Route::get('/parent', function () {
        return view('parent.dashboard');
    })->middleware(['role:parent', 'account.active'])->name('parent.dashboard');

    Route::middleware(['role:parent', 'account.active'])->prefix('parent')->name('parent.')->group(function () {
        Route::get('riwayat', [ParentHistoryController::class, 'index'])->name('history.index');
        Route::post('riwayat/{attendance}/tolak', [ParentHistoryController::class, 'reject'])->name('history.reject');
        Route::post('riwayat/{attendance}/batalkan-penolakan', [ParentHistoryController::class, 'cancelReject'])->name('history.cancel-reject');
        Route::get('tagihan', [ParentBillingController::class, 'index'])->name('billing.index');
        Route::get('complete-data', [ParentBillingController::class, 'completeData'])->name('billing.complete-data');
        Route::post('complete-data', [ParentBillingController::class, 'submitCompleteData'])->name('billing.submit-complete-data');
        Route::post('tagihan/upload/{parentId}/{year}/{month}', [ParentBillingController::class, 'uploadProof'])->name('billing.upload-proof');
        Route::post('tagihan/invoice/{year}/{month}', [ParentBillingController::class, 'downloadInvoice'])->name('billing.download-invoice');
    });

    Route::get('/password/force', [PasswordForceController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('/password/force', [PasswordForceController::class, 'update'])
        ->name('password.force.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/teacher', [ProfileController::class, 'updateTeacher'])->name('profile.teacher.update');
    Route::patch('/profile/bank', [ProfileController::class, 'updateBank'])->name('profile.bank.update');
    Route::post('/profile/founder/{teacher}', [ProfileController::class, 'updateFounder'])->name('profile.founder.update');
    Route::post('/profile/founder/{teacher}/photo', [ProfileController::class, 'uploadFounderPhoto'])->name('profile.founder.photo');
    Route::patch('/profile/fine-settings', [\App\Http\Controllers\Admin\FineSettingsController::class, 'update'])->name('profile.fine-settings.update');
    Route::post('/profile/fine-settings/reset', [\App\Http\Controllers\Admin\FineSettingsController::class, 'reset'])->name('profile.fine-settings.reset');
    Route::post('/profile/photo/upload', [ProfileController::class, 'uploadPhoto'])->name('profile.photo.upload');
    Route::delete('/profile/photo/delete', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public registration routes
Route::get('register-student/success', [RegisterStudentController::class, 'success'])
    ->name('register-student.success');
Route::get('register-student/{token}', [RegisterStudentController::class, 'form'])
    ->name('register-student.form');
Route::post('register-student/{token}', [RegisterStudentController::class, 'submit'])
    ->name('register-student.submit');

Route::get('register-teacher/success', [RegisterTeacherController::class, 'success'])
    ->name('register-teacher.success');
Route::get('register-teacher/{token}', [RegisterTeacherController::class, 'form'])
    ->name('register-teacher.form');
Route::post('register-teacher/{token}', [RegisterTeacherController::class, 'submit'])
    ->name('register-teacher.submit');

Route::get('pdf/parent/{parent}/{filename}', [\App\Http\Controllers\PdfController::class, 'serveParentInvoice'])
    ->middleware('throttle:pdf-access')
    ->name('pdf.parent');
Route::get('pdf/guru/{teacher}/{filename}', [\App\Http\Controllers\PdfController::class, 'serveTeacherSlip'])
    ->middleware('throttle:pdf-access')
    ->name('pdf.guru');

// Public complete-data routes (no auth) for guests redirected from PdfController
Route::get('complete-data/parent/{parent}', [\App\Http\Controllers\Parent\BillingController::class, 'completeDataPublic'])
    ->name('complete-data.parent');
Route::post('complete-data/parent/{parent}', [\App\Http\Controllers\Parent\BillingController::class, 'submitCompleteDataPublic'])
    ->name('complete-data.parent.submit');

Route::get('complete-data/guru/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'completeDataPublic'])
    ->name('complete-data.guru');
Route::post('complete-data/guru/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'submitCompleteDataPublic'])
    ->name('complete-data.guru.submit');

require __DIR__.'/auth.php';