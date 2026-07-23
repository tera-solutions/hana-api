<?php

use App\Modules\HR\Payroll\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

// A teacher's own payroll (lương = giờ dạy × đơn giá/giờ + thưởng − phạt); see
// PayrollController docblock. `generate` is admin-only.
Route::prefix('payroll')->middleware('auth.tera')->group(function () {

    Route::get('/list', [PayrollController::class, 'list'])->middleware('permission:payroll.view');
    Route::get('/detail/{id}', [PayrollController::class, 'detail'])->middleware('permission:payroll.view');

    Route::post('/generate', [PayrollController::class, 'generate'])->middleware('permission:payroll.generate');
    Route::post('/pay/{id}', [PayrollController::class, 'pay'])->middleware('permission:payroll.pay');

});
