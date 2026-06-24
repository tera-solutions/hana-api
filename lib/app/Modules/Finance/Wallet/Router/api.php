<?php

use App\Modules\Finance\Wallet\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// Internal customer wallets + transaction ledger (wallet.md §XV).
Route::prefix('wallet')->middleware('auth.tera')->group(function () {

    Route::get('/list', [WalletController::class, 'list'])->middleware('permission:wallet.view');
    Route::get('/detail/{id}', [WalletController::class, 'detail'])->middleware('permission:wallet.view');
    Route::get('/transactions', [WalletController::class, 'transactions'])->middleware('permission:wallet.transaction.view');

    Route::post('/lock/{id}', [WalletController::class, 'lock'])->middleware('permission:wallet.lock');
    Route::post('/unlock/{id}', [WalletController::class, 'unlock'])->middleware('permission:wallet.lock');

    Route::post('/deposit', [WalletController::class, 'deposit'])->middleware('permission:wallet.deposit');
    Route::post('/payment', [WalletController::class, 'payment'])->middleware('permission:wallet.payment');
    Route::post('/refund', [WalletController::class, 'refund'])->middleware('permission:wallet.refund');
    Route::post('/adjustment', [WalletController::class, 'adjustment'])->middleware('permission:wallet.adjust');

    // Record balance from finance documents.
    Route::post('/from-invoice', [WalletController::class, 'fromInvoice'])->middleware('permission:wallet.payment');
    Route::post('/from-payment', [WalletController::class, 'fromPayment'])->middleware('permission:wallet.deposit');

});
