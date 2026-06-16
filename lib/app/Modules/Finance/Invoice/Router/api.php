<?php

use App\Modules\Finance\Invoice\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// Invoice management (invoice.md) — receivable/payable CRUD, approval, payments.
Route::prefix('invoice')->middleware('auth.tera')->group(function () {

    Route::get('/list', [InvoiceController::class, 'list'])->middleware('permission:fin_invoice.list');
    Route::get('/detail/{id}', [InvoiceController::class, 'detail'])->middleware('permission:fin_invoice.view');

    Route::post('/create', [InvoiceController::class, 'create'])->middleware('permission:fin_invoice.create');
    Route::put('/update/{id}', [InvoiceController::class, 'update'])->middleware('permission:fin_invoice.update');

    Route::post('/approve/{id}', [InvoiceController::class, 'approve'])->middleware('permission:fin_invoice.approve');
    Route::post('/deny/{id}', [InvoiceController::class, 'deny'])->middleware('permission:fin_invoice.approve');
    Route::post('/cancel/{id}', [InvoiceController::class, 'cancel'])->middleware('permission:fin_invoice.cancel');
    Route::post('/refund/{id}', [InvoiceController::class, 'refund'])->middleware('permission:fin_invoice.refund');

    Route::post('/payment/{id}', [InvoiceController::class, 'pay'])->middleware('permission:fin_invoice.pay');
});
