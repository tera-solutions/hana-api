<?php

use App\Modules\Finance\WalletRequest\Http\Controllers\WalletRequestController;
use Illuminate\Support\Facades\Route;

// Deposit/withdraw requests against a teacher's own wallet — no payment gateway,
// admin reviews + settles outside the system, see WalletRequestService docblock.
Route::prefix('wallet-request')->middleware('auth.tera')->group(function () {

    Route::get('/list', [WalletRequestController::class, 'list'])->middleware('permission:wallet_request.list');
    Route::get('/detail/{id}', [WalletRequestController::class, 'detail'])->middleware('permission:wallet_request.view');

    Route::post('/create', [WalletRequestController::class, 'create'])->middleware('permission:wallet_request.create');
    Route::post('/cancel/{id}', [WalletRequestController::class, 'cancel'])->middleware('permission:wallet_request.cancel');

    Route::post('/approve/{id}', [WalletRequestController::class, 'approve'])->middleware('permission:wallet_request.approve');
    Route::post('/reject/{id}', [WalletRequestController::class, 'reject'])->middleware('permission:wallet_request.approve');
    Route::post('/complete/{id}', [WalletRequestController::class, 'complete'])->middleware('permission:wallet_request.approve');

});
