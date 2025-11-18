<?php

use Illuminate\Support\Facades\Route;

Route::name('collector.')->group(function () {
    Route::controller("Gateway\PaymentController")
        ->name('deposit.')
        ->middleware(['auth:collector', 'check.status', 'registration.complete'])
        ->prefix("deposit")
        ->group(function () {
            Route::get('confirm', 'depositConfirm')->name('confirm');
            Route::any('history', 'depositHistoryDriver')->name('history');
            Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
            Route::post('manual', 'manualDepositUpdate')->name('manual.update');
        });
});
