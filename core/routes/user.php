<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NotificationController;

Route::get('/unauthorised', [NotificationController::class, 'unauthorised'])
    ->name('unauthorised');
Route::name('user.')->group(function () {
    Route::middleware(['auth', 'check.status', 'registration.complete'])
        ->prefix('deposit')
        ->name('deposit.')
        ->controller('Gateway\PaymentController')
        ->group(function () {
            Route::get('confirm', 'depositConfirm')->name('confirm');
            Route::any('history', 'depositHistory')->name('history');
            Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
            Route::post('manual', 'manualDepositUpdate')->name('manual.update');
        });
});
