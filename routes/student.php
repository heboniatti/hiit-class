<?php

use App\Http\Controllers\Student\CheckInController;
use App\Http\Controllers\Student\ClassBrowseController;
use App\Http\Controllers\Student\GoalController;
use App\Http\Controllers\Student\HistoryController;
use App\Http\Controllers\Student\MyPlanController;
use App\Http\Controllers\PaymentReceiptController;
use Illuminate\Support\Facades\Route;

// The student area. Teachers and admins have their own management screens, so
// this whole group is closed to them.
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('classes', [ClassBrowseController::class, 'index'])->name('classes.index');
    Route::get('classes/{classSession}', [ClassBrowseController::class, 'show'])->name('classes.show');

    Route::post('classes/{classSession}/check-in', [CheckInController::class, 'store'])
        ->name('check-ins.store');
    Route::delete('classes/{classSession}/check-in', [CheckInController::class, 'destroy'])
        ->name('check-ins.destroy');

    Route::get('goals', GoalController::class)->name('goals');
    Route::get('history', HistoryController::class)->name('history');
    Route::get('my-plan', MyPlanController::class)->name('my-plan');
    Route::post('my-plan/payments/{payment}/receipt', [PaymentReceiptController::class, 'store'])
        ->name('payment-receipts.store');
});
