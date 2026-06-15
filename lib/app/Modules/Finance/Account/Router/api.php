<?php

use App\Modules\Finance\Account\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

// Funds / quỹ (payment.md §VI).
Route::prefix('account')->middleware('auth.tera')->group(function () {

    Route::get('/list', [AccountController::class, 'list'])->middleware('permission:fin_account.list');
    Route::get('/detail/{id}', [AccountController::class, 'detail'])->middleware('permission:fin_account.view');

    Route::post('/create', [AccountController::class, 'create'])->middleware('permission:fin_account.create');
    Route::put('/update/{id}', [AccountController::class, 'update'])->middleware('permission:fin_account.update');

    Route::post('/suspend/{id}', [AccountController::class, 'suspend'])->middleware('permission:fin_account.suspend');
    Route::post('/restore/{id}', [AccountController::class, 'restore'])->middleware('permission:fin_account.restore');
});
