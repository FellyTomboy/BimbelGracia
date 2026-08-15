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
    })->name('dashboard');

    Route::prefix('admin')
        ->middleware('role:admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/', function () {
                return view('admin.dashboard');
            })->name('dashboard');

            Route::get('students', [StudentController::class, 'index'])
                ->name('students.index');
            Route::get('students/inactive', [StudentController::class, 'inactive'])
                ->name('students.inactive');
            Route::any('students/bulk-delete', [StudentController::class, 'bulkDestroy'])
                ->name('students.bulk-destroy');
            Route::delete('students/{student}', [StudentController::class, 'destroy'])
                ->name('students.destroy');
            Route::post('students/{student}/restore', [StudentController::class, 'restore'])
                ->name('students.restore');

            Route::resource('teachers', TeacherController::class)->except(['show']);
            Route::get('teachers/inactive', [TeacherController::class, 'inactive'])
                ->name('teachers.inactive');
            Route::post('teachers/{teacher}/restore', [TeacherController::class, 'restore'])
                ->name('teachers.restore');
            Route::post('teachers/bulk-destroy', [TeacherController::class, 'bulkDestroy'])
                ->name('teachers.bulk-destroy');

            Route::resource('programs', ProgramController::class)->except(['show']);
            Route::get('programs/inactive', [ProgramController::class, 'inactive'])
                ->name('programs.inactive');
            Route::post('programs/{program}/restore', [ProgramController::class, 'restore'])
                ->name('programs.restore');
            Route::post('programs/bulk-destroy', [ProgramController::class, 'bulkDestroy'])
                ->name('programs.bulk-destroy');

            Route::resource('enrollments', EnrollmentController::class)->except(['show']);
            Route::get('enrollments/inactive', [EnrollmentController::class, 'inactive'])
                ->name('enrollments.inactive');
            Route::post('enrollments/{enrollment}/restore', [EnrollmentController::class, 'restore'])
                ->name('enrollments.restore');
            Route::post('enrollments/bulk-destroy', [EnrollmentController::class, 'bulkDestroy'])
                ->name('enrollments.bulk-destroy');

            Route::resource('lesson-offers', AdminLessonOfferController::class)->except(['show']);
            Route::get('lesson-offers/inactive', [AdminLessonOfferController::class, 'inactive'])
                ->name('lesson-offers.inactive');
            Route::post('lesson-offers/{lessonOffer}/restore', [AdminLessonOfferController::class, 'restore'])
                ->name('lesson-offers.restore');

            Route::resource('bank-accounts', BankAccountController::class)->except(['show']);
            Route::get('bank-accounts/inactive', [BankAccountController::class, 'inactive'])
                ->name('bank-accounts.inactive');
            Route::post('bank-accounts/{bankAccount}/restore', [BankAccountController::class, 'restore'])
                ->name('bank-accounts.restore');

            Route::resource('parents', ParentController::class)->except(['show']);
            Route::get('parents/inactive', [ParentController::class, 'inactive'])
                ->name('parents.inactive');
            Route::post('parents/{parent}/hibernate', [ParentController::class, 'hibernate'])
                ->name('parents.hibernate');
            Route::post('parents/{parent}/restore', [ParentController::class, 'restore'])
                ->name('parents.restore');
            Route::post('parents/bulk-destroy', [ParentController::class, 'bulkDestroy'])
                ->name('parents.bulk-destroy');
            Route::delete('parents/{parent}/students/{student}', [ParentController::class, 'removeStudent'])
                ->name('parents.remove-student');
            Route::post('parents/{parent}/add-student', [ParentController::class, 'addStudent'])
                ->name('parents.add-student');
            Route::post('parents/{parent}/change-password', [ParentController::class, 'changePassword'])
                ->name('parents.change-password');
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
            Route::get('presensi/{attendance}', [AdminAttendanceController::class, 'show'])
                ->name('presensi.show');
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
            Route::get('analysis/guru', [AnalysisController::class, 'guru'])
                ->name('analysis.guru');
            Route::get('payments/ortu', [AnalysisController::class, 'paymentsOrtu'])
                ->name('payments.ortu');
            Route::get('payments/guru', [AnalysisController::class, 'paymentsGuru'])
                ->name('payments.guru');
            Route::post('payments/ortu/{attendance}/payment', [AnalysisController::class, 'updateParentPayment'])
                ->name('payments.ortu.payment');
            Route::post('payments/guru/{attendance}/payment', [AnalysisController::class, 'updateTeacherPayment'])
                ->name('payments.guru.payment');
            Route::post('payments/{attendance}/confirm-proof', [AnalysisController::class, 'confirmPaymentProof'])
                ->name('payments.confirm-proof');
            Route::post('analysis/generate-invoice/{student}/{month}/{year}', [AnalysisController::class, 'generateInvoice'])
                ->name('analysis.generate-invoice');
            Route::post('analysis/generate-salary/{teacher}/{month}/{year}', [AnalysisController::class, 'generateSalary'])
                ->name('analysis.generate-salary');

            Route::get('new-students', [NewStudentController::class, 'index'])
                ->name('new-students.index');
            Route::post('new-students/{newStudent}/convert', [NewStudentController::class, 'convert'])
                ->name('new-students.convert');
            Route::delete('new-students/{newStudent}', [NewStudentController::class, 'destroy'])
                ->name('new-students.destroy');
            Route::delete('new-students/all', [NewStudentController::class, 'destroyAll'])
                ->name('new-students.destroy-all');

            Route::get('teacher-registrants', [TeacherRegistrantController::class, 'index'])
                ->name('teacher-registrants.index');
            Route::post('teacher-registrants/{teacherRegistrant}/convert', [TeacherRegistrantController::class, 'convert'])
                ->name('teacher-registrants.convert');
            Route::delete('teacher-registrants/{teacherRegistrant}', [TeacherRegistrantController::class, 'destroy'])
                ->name('teacher-registrants.destroy');
            Route::delete('teacher-registrants/all', [TeacherRegistrantController::class, 'destroyAll'])
                ->name('teacher-registrants.destroy-all');

            Route::resource('documents', AdminDocumentController::class)->except(['show']);
            Route::get('discounts', [DiscountController::class, 'index'])
                ->name('discounts.index');
            Route::post('discounts', [DiscountController::class, 'store'])
                ->name('discounts.store');

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
    })->middleware('role:guru')->name('guru.dashboard');

    Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
        Route::get('presensi', [GuruAttendanceController::class, 'index'])->name('presensi.index');
        Route::get('presensi/create', [GuruAttendanceController::class, 'create'])->name('presensi.create');
        Route::post('presensi', [GuruAttendanceController::class, 'store'])->name('presensi.store');
        Route::get('presensi/{attendance}/edit', [GuruAttendanceController::class, 'edit'])->name('presensi.edit');
        Route::put('presensi/{attendance}', [GuruAttendanceController::class, 'update'])->name('presensi.update');
        Route::get('tawaran', [GuruLessonOfferController::class, 'index'])->name('tawaran.index');
        Route::get('riwayat', [GuruHistoryController::class, 'index'])->name('history.index');
        Route::get('proyeksi-gaji', [GuruSalaryProjectionController::class, 'index'])->name('salary-projection.index');
        Route::get('complete-data', [\App\Http\Controllers\Admin\TeacherController::class, 'completeData'])->name('complete-data');
        Route::post('complete-data', [\App\Http\Controllers\Admin\TeacherController::class, 'submitCompleteData'])->name('complete-data.store');
        Route::get('documents', [GuruDocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [GuruDocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}/verify-password', [GuruDocumentController::class, 'verifyPassword'])->name('documents.verify-password');
        Route::get('documents/{document}/download', [GuruDocumentController::class, 'download'])->name('documents.download');
    });

    Route::get('/parent', function () {
        return view('parent.dashboard');
    })->middleware('role:parent')->name('parent.dashboard');

    Route::middleware('role:parent')->prefix('parent')->name('parent.')->group(function () {
        Route::get('riwayat', [ParentHistoryController::class, 'index'])->name('history.index');
        Route::post('riwayat/{attendance}/tolak', [ParentHistoryController::class, 'reject'])->name('history.reject');
        Route::post('riwayat/{attendance}/batalkan-penolakan', [ParentHistoryController::class, 'cancelReject'])->name('history.cancel-reject');
        Route::get('tagihan', [ParentBillingController::class, 'index'])->name('billing.index');
        Route::get('complete-data', [ParentBillingController::class, 'completeData'])->name('billing.complete-data');
        Route::post('complete-data', [ParentBillingController::class, 'submitCompleteData'])->name('billing.submit-complete-data');
        Route::post('tagihan/{attendance}/upload', [ParentBillingController::class, 'uploadProof'])->name('billing.upload-proof');
        Route::post('tagihan/invoice/{year}/{month}', [ParentBillingController::class, 'downloadInvoice'])->name('billing.download-invoice');
    });

    Route::get('/password/force', [PasswordForceController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('/password/force', [PasswordForceController::class, 'update'])
        ->name('password.force.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/bank', [ProfileController::class, 'updateBank'])->name('profile.bank.update');
    Route::post('/profile/founder/{teacher}', [ProfileController::class, 'updateFounder'])->name('profile.founder.update');
    Route::post('/profile/founder/{teacher}/photo', [ProfileController::class, 'uploadFounderPhoto'])->name('profile.founder.photo');
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

require __DIR__.'/auth.php';
