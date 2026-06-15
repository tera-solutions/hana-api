<?php

use App\Modules\Finance\Payment\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment transactions (payment.md) — cash-flow CRUD + confirm/cancel/reverse/refund.
Route::prefix('payment')->middleware('auth.tera')->group(function () {

    Route::get('/list', [PaymentController::class, 'list'])->middleware('permission:fin_payment.list');
    Route::get('/detail/{id}', [PaymentController::class, 'detail'])->middleware('permission:fin_payment.view');
    Route::get('/receipt/{id}', [PaymentController::class, 'receipt'])->middleware('permission:fin_payment.view');

    Route::post('/create', [PaymentController::class, 'create'])->middleware('permission:fin_payment.create');
    Route::put('/update/{id}', [PaymentController::class, 'update'])->middleware('permission:fin_payment.update');

    Route::post('/confirm/{id}', [PaymentController::class, 'confirm'])->middleware('permission:fin_payment.confirm');
    Route::post('/cancel/{id}', [PaymentController::class, 'cancel'])->middleware('permission:fin_payment.cancel');
    Route::post('/reverse/{id}', [PaymentController::class, 'reverse'])->middleware('permission:fin_payment.reverse');
    Route::post('/refund/{id}', [PaymentController::class, 'refund'])->middleware('permission:fin_payment.refund');
});
