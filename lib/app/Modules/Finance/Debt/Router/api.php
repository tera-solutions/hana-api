<?php

use App\Modules\Finance\Debt\Http\Controllers\DebtController;
use Illuminate\Support\Facades\Route;

// Debt management (debt.md) — computed AR/AP reporting + adjustments/write-off/collection.
Route::prefix('debt')->middleware('auth.tera')->group(function () {

    Route::get('/list', [DebtController::class, 'list'])->middleware('permission:fin_debt.list');
    Route::get('/aging', [DebtController::class, 'aging'])->middleware('permission:fin_debt.list');
    Route::get('/dashboard', [DebtController::class, 'dashboard'])->middleware('permission:fin_debt.list');
    Route::get('/detail/{invoiceId}', [DebtController::class, 'detail'])->middleware('permission:fin_debt.view');

    Route::post('/adjust/{invoiceId}', [DebtController::class, 'adjust'])->middleware('permission:fin_debt.adjust');

    Route::post('/writeoff/{invoiceId}', [DebtController::class, 'writeoff'])->middleware('permission:fin_debt.writeoff');
    Route::post('/writeoff/approve/{id}', [DebtController::class, 'approveWriteoff'])->middleware('permission:fin_debt.writeoff');
    Route::post('/writeoff/deny/{id}', [DebtController::class, 'denyWriteoff'])->middleware('permission:fin_debt.writeoff');

    Route::post('/collect/{invoiceId}', [DebtController::class, 'collect'])->middleware('permission:fin_debt.collect');
    Route::post('/reconcile', [DebtController::class, 'reconcile'])->middleware('permission:fin_debt.reconcile');
});
