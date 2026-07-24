<?php

use App\Modules\Finance\BusinessBankAccount\Http\Controllers\BusinessBankAccountController;
use Illuminate\Support\Facades\Route;

// The business's own receiving bank accounts, used to build invoice payment QR codes.
Route::prefix('business-bank-account')->middleware('auth.tera')->group(function () {

    Route::get('/list', [BusinessBankAccountController::class, 'list'])->middleware('permission:business_bank_account.list');
    Route::get('/detail/{id}', [BusinessBankAccountController::class, 'detail'])->middleware('permission:business_bank_account.view');

    Route::post('/create', [BusinessBankAccountController::class, 'create'])->middleware('permission:business_bank_account.create');
    Route::put('/update/{id}', [BusinessBankAccountController::class, 'update'])->middleware('permission:business_bank_account.update');

    Route::post('/suspend/{id}', [BusinessBankAccountController::class, 'suspend'])->middleware('permission:business_bank_account.suspend');
    Route::post('/restore/{id}', [BusinessBankAccountController::class, 'restore'])->middleware('permission:business_bank_account.restore');

    Route::patch('/set-default/{id}', [BusinessBankAccountController::class, 'setDefault'])->middleware('permission:business_bank_account.update')->whereNumber('id');
    Route::get('/qr/{id}', [BusinessBankAccountController::class, 'qr'])->middleware('permission:business_bank_account.view')->whereNumber('id');
});
