<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentReceiptController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// No "verified" middleware anywhere: students sign in with a phone and a PIN
// and may have no email at all, so there is nothing to verify.
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('payments/{payment}/receipt', [PaymentReceiptController::class, 'show'])
        ->name('payment-receipts.show');
});

require __DIR__.'/student.php';
require __DIR__.'/management.php';
require __DIR__.'/settings.php';
