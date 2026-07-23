<?php

use App\Modules\Finance\Debt\Http\Controllers\DebtController;
use Illuminate\Support\Facades\Route;

// Debt management (debt.md) — computed AR/AP reporting + adjustments/write-off/collection.
Route::prefix('debt')->middleware('auth.tera')->group(function () {

    Route::get('/list', [DebtController::class, 'list'])->middleware('permission:debt.list');
    Route::get('/aging', [DebtController::class, 'aging'])->middleware('permission:debt.list');
    Route::get('/dashboard', [DebtController::class, 'dashboard'])->middleware('permission:debt.list');
    Route::get('/detail/{invoiceId}', [DebtController::class, 'detail'])->middleware('permission:debt.view');

    Route::post('/adjust/{invoiceId}', [DebtController::class, 'adjust'])->middleware('permission:debt.adjust');

    Route::post('/writeoff/{invoiceId}', [DebtController::class, 'writeoff'])->middleware('permission:debt.writeoff');
    Route::post('/writeoff/approve/{id}', [DebtController::class, 'approveWriteoff'])->middleware('permission:debt.writeoff');
    Route::post('/writeoff/deny/{id}', [DebtController::class, 'denyWriteoff'])->middleware('permission:debt.writeoff');

    Route::post('/collect/{invoiceId}', [DebtController::class, 'collect'])->middleware('permission:debt.collect');
    Route::post('/reconcile', [DebtController::class, 'reconcile'])->middleware('permission:debt.reconcile');
});
