<?php

use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\AttendanceWindowController;
use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\ClassReportController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\MonthlyAttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Guru\MonthlyAttendanceController as GuruAttendanceController;
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
            Route::post('students/{student}/restore', [StudentController::class, 'restore'])
                ->name('students.restore');

            Route::resource('teachers', TeacherController::class)->except(['show']);
            Route::post('teachers/{teacher}/restore', [TeacherController::class, 'restore'])
                ->name('teachers.restore');

            Route::resource('lessons', LessonController::class)->except(['show']);
            Route::post('lessons/{lesson}/restore', [LessonController::class, 'restore'])
                ->name('lessons.restore');

            Route::resource('class-groups', ClassGroupController::class)->except(['show']);
            Route::post('class-groups/{classGroup}/restore', [ClassGroupController::class, 'restore'])
                ->name('class-groups.restore');

            Route::resource('class-sessions', ClassSessionController::class)->except(['show']);
            Route::get('class-sessions/{classSession}', [ClassSessionController::class, 'show'])
                ->name('class-sessions.show');
            Route::post('class-sessions/{classSession}/attendance', [ClassSessionController::class, 'updateAttendance'])
                ->name('class-sessions.attendance');
            Route::post('class-sessions/{classSession}/restore', [ClassSessionController::class, 'restore'])
                ->name('class-sessions.restore');

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
            Route::post('presensi/{attendance}/lesson', [AdminAttendanceController::class, 'updateLesson'])
                ->name('presensi.lesson');
            Route::post('presensi/{attendance}/validate', [AdminAttendanceController::class, 'validateAttendance'])
                ->name('presensi.validate');

            Route::get('analysis/ortu', [AnalysisController::class, 'ortu'])
                ->name('analysis.ortu');
            Route::get('analysis/guru', [AnalysisController::class, 'guru'])
                ->name('analysis.guru');
            Route::post('analysis/ortu/{attendance}/payment', [AnalysisController::class, 'updateParentPayment'])
                ->name('analysis.ortu.payment');
            Route::post('analysis/guru/{attendance}/payment', [AnalysisController::class, 'updateTeacherPayment'])
                ->name('analysis.guru.payment');

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
            Route::get('export/teachers', [ExportController::class, 'teachers'])
                ->name('export.teachers');
            Route::get('export/lessons', [ExportController::class, 'lessons'])
                ->name('export.lessons');
            Route::get('export/attendances', [ExportController::class, 'attendances'])
                ->name('export.attendances');
            Route::get('export/class-groups', [ExportController::class, 'classGroups'])
                ->name('export.class-groups');
            Route::get('export/class-sessions', [ExportController::class, 'classSessions'])
                ->name('export.class-sessions');
            Route::get('export/audit', [ExportController::class, 'auditLogs'])
                ->name('export.audit');
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
    });

    Route::get('/murid', function () {
        return view('murid.dashboard');
    })->middleware('role:murid')->name('murid.dashboard');

    Route::get('/password/force', [PasswordForceController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('/password/force', [PasswordForceController::class, 'update'])
        ->name('password.force.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
