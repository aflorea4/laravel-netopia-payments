<?php

use Illuminate\Support\Facades\Route;
use Aflorea4\NetopiaPayments\Http\Controllers\NetopiaPaymentController;

Route::group(['prefix' => 'netopia', 'middleware' => ['web']], function () {
    // Payment confirmation route (IPN - Instant Payment Notification)
    Route::post('confirm', [NetopiaPaymentController::class, 'confirm'])->name('netopia.confirm');
    
    // Payment return route (redirect after payment)
    Route::get('return', [NetopiaPaymentController::class, 'return'])->name('netopia.return');
});
