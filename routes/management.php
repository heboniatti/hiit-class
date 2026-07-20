<?php

use App\Http\Controllers\Management\AttendanceController;
use App\Http\Controllers\Management\ClassSessionController;
use App\Http\Controllers\Management\DashboardController;
use App\Http\Controllers\Management\GeneralSettingsController;
use App\Http\Controllers\Management\GoalController;
use App\Http\Controllers\Management\OverviewController;
use App\Http\Controllers\Management\PaymentController;
use App\Http\Controllers\Management\PlanController;
use App\Http\Controllers\Management\ReportController;
use App\Http\Controllers\Management\StudentController;
use App\Http\Controllers\Management\StudentPinController;
use App\Http\Controllers\Management\TeacherController;
use Illuminate\Support\Facades\Route;

// The management area. Administrators inherit everything a teacher can do, so
// both roles share this group; admin-only screens land in their own group in
// phase 4.
Route::middleware(['auth', 'role:teacher,admin'])
    ->prefix('management')
    ->name('management.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('schedule', [ClassSessionController::class, 'index'])->name('schedule');

        // "create" is declared before the {classSession} routes so it is never
        // swallowed as a class id.
        Route::get('classes/create', [ClassSessionController::class, 'create'])->name('classes.create');
        Route::post('classes', [ClassSessionController::class, 'store'])->name('classes.store');
        Route::get('classes/{classSession}/edit', [ClassSessionController::class, 'edit'])->name('classes.edit');
        Route::put('classes/{classSession}', [ClassSessionController::class, 'update'])->name('classes.update');
        Route::delete('classes/{classSession}', [ClassSessionController::class, 'destroy'])->name('classes.destroy');

        Route::get('classes/{classSession}/attendance', [AttendanceController::class, 'edit'])
            ->name('classes.attendance.edit');
        Route::put('classes/{classSession}/attendance', [AttendanceController::class, 'update'])
            ->name('classes.attendance.update');

        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');

        Route::put('students/{student}/pin', [StudentPinController::class, 'update'])
            ->name('students.pin.update');

        // Plans have no delete: every enrollment that ever used one points at
        // it, so retiring a plan means switching it off.
        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

        Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
        Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
        Route::put('goals/{goal}', [GoalController::class, 'update'])->name('goals.update');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::put('payments/{payment}/pay', [PaymentController::class, 'markAsPaid'])->name('payments.pay');
    });

// Administrators only. The spec puts general reports and system settings under
// the administrator alone, so these sit outside the shared group rather than
// being hidden from teachers in the interface.
Route::middleware(['auth', 'role:admin'])
    ->prefix('management')
    ->name('management.')
    ->group(function () {
        Route::get('overview', OverviewController::class)->name('overview');

        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('settings', [GeneralSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [GeneralSettingsController::class, 'update'])->name('settings.update');
    });
