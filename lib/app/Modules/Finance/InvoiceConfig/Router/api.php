<?php

use App\Modules\Finance\InvoiceConfig\Http\Controllers\InvoiceConfigController;
use Illuminate\Support\Facades\Route;

// Per-business recurring invoice generation settings.
Route::prefix('invoice-config')->middleware('auth.tera')->group(function () {

    Route::get('/', [InvoiceConfigController::class, 'show'])->middleware('permission:invoice_config.view');
    Route::put('/', [InvoiceConfigController::class, 'update'])->middleware('permission:invoice_config.update');
    Route::post('/generate-now', [InvoiceConfigController::class, 'generateNow'])->middleware('permission:invoice_config.update');
});
