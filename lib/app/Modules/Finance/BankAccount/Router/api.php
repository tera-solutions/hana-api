<?php

use App\Modules\Finance\BankAccount\Http\Controllers\BankAccountController;
use Illuminate\Support\Facades\Route;

// Self-service access to the acting teacher's own HR profile bank account —
// scoped to the token's own user, never another teacher's.
Route::prefix('bank-account')->middleware('auth.tera')->group(function () {

    Route::get('/me', [BankAccountController::class, 'me'])->middleware('permission:bank_account.view');
    Route::put('/me', [BankAccountController::class, 'update'])->middleware('permission:bank_account.update');

});
