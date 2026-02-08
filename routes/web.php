<?php

use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\AttendanceWindowController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\ClassReportController;
use App\Http\Controllers\Admin\ClassStudentController;
use App\Http\Controllers\Admin\ClassStudentSessionController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\LessonOfferController as AdminLessonOfferController;
use App\Http\Controllers\Admin\MonthlyAttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Guru\LessonOfferController as GuruLessonOfferController;
use App\Http\Controllers\Guru\MonthlyAttendanceController as GuruAttendanceController;
use App\Http\Controllers\Guru\HistoryController as GuruHistoryController;
use App\Http\Controllers\Guru\SalaryProjectionController as GuruSalaryProjectionController;
use App\Http\Controllers\Murid\BillingController as MuridBillingController;
use App\Http\Controllers\Murid\HistoryController as MuridHistoryController;
use App\Http\Controllers\PasswordForceController;
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

            Route::resource('students', StudentController::class)->except(['show']);
            Route::get('students/inactive', [StudentController::class, 'inactive'])
                ->name('students.inactive');
            Route::post('students/{student}/restore', [StudentController::class, 'restore'])
                ->name('students.restore');

            Route::resource('teachers', TeacherController::class)->except(['show']);
            Route::get('teachers/inactive', [TeacherController::class, 'inactive'])
                ->name('teachers.inactive');
            Route::post('teachers/{teacher}/restore', [TeacherController::class, 'restore'])
                ->name('teachers.restore');

            Route::resource('programs', ProgramController::class)->except(['show']);
            Route::get('programs/inactive', [ProgramController::class, 'inactive'])
                ->name('programs.inactive');
            Route::post('programs/{program}/restore', [ProgramController::class, 'restore'])
                ->name('programs.restore');

            Route::resource('enrollments', EnrollmentController::class)->except(['show']);
            Route::get('enrollments/inactive', [EnrollmentController::class, 'inactive'])
                ->name('enrollments.inactive');
            Route::post('enrollments/{enrollment}/restore', [EnrollmentController::class, 'restore'])
                ->name('enrollments.restore');

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

            Route::resource('class-students', ClassStudentController::class)->except(['show']);
            Route::get('class-students/inactive', [ClassStudentController::class, 'inactive'])
                ->name('class-students.inactive');
            Route::post('class-students/{classStudent}/restore', [ClassStudentController::class, 'restore'])
                ->name('class-students.restore');

            Route::resource('class-student-sessions', ClassStudentSessionController::class)->except(['show']);
            Route::get('class-student-sessions/calendar', [ClassStudentSessionController::class, 'calendar'])
                ->name('class-student-sessions.calendar');
            Route::get('class-student-sessions/inactive', [ClassStudentSessionController::class, 'inactive'])
                ->name('class-student-sessions.inactive');
            Route::post('class-student-sessions/{classStudentSession}/restore', [ClassStudentSessionController::class, 'restore'])
                ->name('class-student-sessions.restore');

            Route::get('attendance-windows', [AttendanceWindowController::class, 'index'])
                ->name('attendance-windows.index');
            Route::post('attendance-windows', [AttendanceWindowController::class, 'store'])
                ->name('attendance-windows.store');
            Route::post('attendance-windows/{attendanceWindow}/close', [AttendanceWindowController::class, 'close'])
                ->name('attendance-windows.close');

            Route::get('presensi', [AdminAttendanceController::class, 'index'])
                ->name('presensi.index');
            Route::get('presensi/{attendance}', [AdminAttendanceController::class, 'show'])
                ->name('presensi.show');
            Route::post('presensi/{attendance}/enrollment', [AdminAttendanceController::class, 'updateEnrollment'])
                ->name('presensi.enrollment');
            Route::post('presensi/{attendance}/validate', [AdminAttendanceController::class, 'validateAttendance'])
                ->name('presensi.validate');

            Route::get('analysis/ortu', [AnalysisController::class, 'ortu'])
                ->name('analysis.ortu');
            Route::post('analysis/ortu/discount', [AnalysisController::class, 'updateEnrollmentDiscount'])
                ->name('analysis.ortu-discount');
            Route::get('analysis/ortu-kelas', [AnalysisController::class, 'ortuKelas'])
                ->name('analysis.ortu-kelas');
            Route::post('analysis/ortu-kelas/discount', [AnalysisController::class, 'updateClassDiscount'])
                ->name('analysis.ortu-class-discount');
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

            Route::get('discounts', [DiscountController::class, 'index'])
                ->name('discounts.index');
            Route::post('discounts', [DiscountController::class, 'store'])
                ->name('discounts.store');

            Route::get('finance', [FinanceController::class, 'index'])
                ->name('finance.index');

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
            Route::get('export/class-groups', [ExportController::class, 'classGroups'])
                ->name('export.class-groups');
            Route::get('export/class-groups/excel', [ExportController::class, 'classGroupsExcel'])
                ->name('export.class-groups.excel');
            Route::get('export/class-groups/pdf', [ExportController::class, 'classGroupsPdf'])
                ->name('export.class-groups.pdf');
            Route::get('export/class-sessions', [ExportController::class, 'classSessions'])
                ->name('export.class-sessions');
            Route::get('export/class-sessions/excel', [ExportController::class, 'classSessionsExcel'])
                ->name('export.class-sessions.excel');
            Route::get('export/class-sessions/pdf', [ExportController::class, 'classSessionsPdf'])
                ->name('export.class-sessions.pdf');
            Route::get('export/class-sessions/monthly/excel', [ExportController::class, 'classSessionsMonthlyExcel'])
                ->name('export.class-sessions.monthly.excel');
            Route::get('export/class-sessions/monthly/pdf', [ExportController::class, 'classSessionsMonthlyPdf'])
                ->name('export.class-sessions.monthly.pdf');
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
    });

    Route::get('/murid', function () {
        return view('murid.dashboard');
    })->middleware('role:murid')->name('murid.dashboard');

    Route::middleware('role:murid')->prefix('murid')->name('murid.')->group(function () {
        Route::get('riwayat', [MuridHistoryController::class, 'index'])->name('history.index');
        Route::get('tagihan', [MuridBillingController::class, 'index'])->name('billing.index');
    });

    Route::get('/password/force', [PasswordForceController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('/password/force', [PasswordForceController::class, 'update'])
        ->name('password.force.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
